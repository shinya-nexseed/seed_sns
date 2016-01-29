<?php 
    session_start();
    require('dbconnect.php');

    // htmlspecialcharsのショートカット
    function h($value) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    // つぶやき内のURLにリンクを設置
    function makeLink($value) {
        return mb_ereg_replace("(https?)(://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)",'<a href="\1\2" target="_blank">\1\2</a>', $value);
    }

    if (isset($_SESSION['member_id']) && $_SESSION['time'] + 3600 > time() ) {
        // ログインしている
        $_SESSION['time'] = time();

        $sql = sprintf('SELECT * FROM members WHERE member_id=%d',
            mysqli_real_escape_string($db, $_SESSION['member_id'])
        );

        $record = mysqli_query($db, $sql) or die(mysqli_error($db));
        $member = mysqli_fetch_assoc($record);
    } else {
        // ログインしていない
        header('Location: login.php');
        exit();
    }

    // 投稿を記録する
    if (!empty($_POST)) {
        if ($_POST['tweet'] != '') {
            $sql = sprintf('INSERT INTO tweets SET member_id=%d, tweet="%s", reply_tweet_id=%d, created=NOW()',
                mysqli_real_escape_string($db, $member['member_id']),
                mysqli_real_escape_string($db, $_POST['tweet']), // ,を忘れずに
                mysqli_real_escape_string($db, $_POST['reply_tweet_id']) // ここ足すとnullじゃなくて0が入るようになる
            );
            mysqli_query($db,$sql) or die(mysqli_error($db));

            // 表示される画面に変化はないが、情報登録の重複を防ぐために必要
            header('Location: index.php');
            exit();
        }
    }

    // ページング
    // URLに?page=2などのページ番号があれば、それを取得して$pageに代入
    if (isset($_REQUEST['page'])) {
        $page = $_REQUEST['page'];    
    }
    if (empty($page)) {
        $page = 1;
    }
    if ($page == '') {
        $page = 1;
    }

    // max関数
    // ()内に指定した複数データから一番大きい値を取得する
    $page = max($page,1);
    // もしユーザーがURLに?page=0.8のような値を入れてリクエストした場合に、強制的に1ページ目にとぶように処理している

    // 最終ページを取得する
    $sql = 'SELECT COUNT(*) AS cnt FROM tweets';
    // SELECT COUNTを使ってtweetsテーブルのデータの件数を取得

    $recordSet = mysqli_query($db,$sql) or die(mysqli_error($db));
    $table = mysqli_fetch_assoc($recordSet);

    // ceil関数
    // 小数点以下切り上げて数字を作る 例:1.8が指定された場合は切り上げて2を返す
    $maxPage = ceil($table['cnt'] / 5); // ← 5件がマックスで表示したいデータ件数のため5で割る
    
    // もしユーザーがURLに?page=100などのような大きすぎる値を入れてリクエストを送ってきた際に、
    // DBに保存されているデータの件数を5で割り最大ページ数を算出し、
    // もしそれ以上の値がセットされていた場合はmin関数を使用して最大ページ数で表示する。

    // min関数
    // ()内に指定した複数データから一番小さい値を取得する
    $page = min($page, $maxPage);

    // 1ページなら、$startには0が代入され、DBからSELECT ~ LIMIT 0,5とすることで、
    // 1個目のデータ(idが1のもの)から5件取得するための$startを用意
    // もし指定されたページが2ページ目なら、$pageには2が入り、計算処理の結果
    // $startには5がはいります。
    // その後SELECT ~ LIMIT 5,5というsql文が発行され、
    // 6個目のデータから5件取得する処理が作られます。
    $start = ($page - 1) * 5;
    $start = max(0, $start);


    $sql = sprintf('SELECT m.nick_name, m.picture_path, t.* FROM members m, tweets t ORDER BY t.created DESC LIMIT %d, 5',
        $start
    );
    $tweets = mysqli_query($db,$sql) or die(mysqli_error($db));

    // 返信の場合
    if (isset($_REQUEST['res'])) {
        $sql = sprintf('SELECT m.nick_name, m.picture_path, t.* FROM members m, tweets t WHERE t.tweet_id=%d ORDER BY t.created DESC',
            mysqli_real_escape_string($db, $_REQUEST['res'])
        );
        $record = mysqli_query($db, $sql) or die(mysqli_error($db));
        $table = mysqli_fetch_assoc($record);
        $tweet = ' >> @' . $table['nick_name'] . ' ' . $table['tweet'];
    }
 ?>

<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>SeedSNS</title>

    <!-- Bootstrap -->
    <link href="assets/css/bootstrap.css" rel="stylesheet">
    <link href="assets/font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="assets/css/form.css" rel="stylesheet">
    <link href="assets/css/timeline.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">


    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
  <nav class="navbar navbar-default navbar-fixed-top">
      <div class="container">
          <!-- Brand and toggle get grouped for better mobile display -->
          <div class="navbar-header page-scroll">
              <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                  <span class="sr-only">Toggle navigation</span>
                  <span class="icon-bar"></span>
                  <span class="icon-bar"></span>
                  <span class="icon-bar"></span>
              </button>
              <a class="navbar-brand" href="index.html"><span class="strong-title"><i class="fa fa-twitter-square"></i> Seed SNS</span></a>
          </div>
          <!-- Collect the nav links, forms, and other content for toggling -->
          <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
              <ul class="nav navbar-nav navbar-right">
                <li><a href="logout.php">ログアウト</a></li>
              </ul>
          </div>
          <!-- /.navbar-collapse -->
      </div>
      <!-- /.container-fluid -->
  </nav>

  <div class="container">
    <div class="row">
      <div class="col-md-4 content-margin-top">
        <legend>ようこそ<?php echo h($member['nick_name']); ?>さん！</legend>
        <form method="post" action="" class="form-horizontal" role="form">
            <!-- つぶやき -->
            <div class="form-group">
              <label class="col-sm-4 control-label">つぶやき</label>
              <div class="col-sm-8">
                <!-- tweet用にフォームを修正 -->

                <?php if (isset($_REQUEST['res'])): ?>
                    <textarea name="tweet" cols="50" rows="5" class="form-control" placeholder="例：Hello World!"><?php echo h($tweet); ?></textarea>
                    <input type="hidden" name="reply_tweet_id" value="<?php echo h($_REQUEST['res']); ?>">
                <?php else: ?>
                    <textarea name="tweet" cols="50" rows="5" class="form-control" placeholder="例：Hello World!"></textarea>
                <?php endif; ?>
              </div>
            </div>

          <!-- ページング用のボタン設置 -->
          <ul class="paging">
            <input type="submit" class="btn btn-info" value="つぶやく"> -|-
            <?php if ($page > 1) { ?>
                <li><a href="index.php?page=<?php print($page - 1); ?>" class="btn btn-default">前</a></li>
            <?php } else { ?>
                <li>前</li>
            <?php } ?>
             | 
            <?php if ($page < $maxPage) { ?>
                <li><a href="index.php?page=<?php print($page + 1); ?>" class="btn btn-default">次</a></li>
            <?php } else { ?>
                <li>次</li>
            <?php } ?>
          </ul>
        </form>
      </div>

      <div class="col-md-8 content-margin-top">
        
        <?php while ($tweet = mysqli_fetch_assoc($tweets)): ?>
            <div class="msg">
              <img src="member_picture/<?php echo h($tweet['picture_path']); ?>" width="48" height="48">
              <p>
                <?php echo makeLink(h($tweet['tweet'])); ?><span class="name"> (<?php echo h($tweet['nick_name']); ?>) </span>
                [<a href="index.php?res=<?php echo h($tweet['tweet_id']) ?>">Re</a>]
              </p>
              <p class="day">
                <a href="view.php?id=<?php echo h($tweet['tweet_id']); ?>">
                  <?php echo h($tweet['created']); ?>
                </a>
                <?php if ($tweet['reply_tweet_id']): ?>
                    <a href="view.php?id=<?php echo h($tweet['reply_tweet_id']); ?>">返信元のつぶやきへ</a>
                <?php endif; ?>
                <?php if ($_SESSION['member_id'] == $tweet['member_id']): ?>
                    [<a href="delete.php?id=<?php echo h($tweet['tweet_id']) ?>" style="color: #F33;">削除</a>]
                <?php endif; ?>
              </p>
            </div>
        <?php endwhile; ?>
      
      </div>
    </div>
  </div>

    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
  </body>
</html>
