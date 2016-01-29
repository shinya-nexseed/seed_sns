<?php 
    session_start();
    require('dbconnect.php');

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

    // 投稿を取得する
    $sql = 'SELECT m.nick_name, m.picture_path, t.* FROM members m, tweets t ORDER BY t.created DESC';
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
              </ul>
          </div>
          <!-- /.navbar-collapse -->
      </div>
      <!-- /.container-fluid -->
  </nav>

  <div class="container">
    <div class="row">
      <div class="col-md-4 content-margin-top">
        <legend>ようこそ<?php echo htmlspecialchars($member['nick_name']); ?>さん！</legend>
        <form method="post" action="" class="form-horizontal" role="form">
            <!-- つぶやき -->
            <div class="form-group">
              <label class="col-sm-4 control-label">つぶやき</label>
              <div class="col-sm-8">
                <!-- tweet用にフォームを修正 -->

                <?php if (isset($_REQUEST['res'])): ?>
                    <textarea name="tweet" cols="50" rows="5" class="form-control" placeholder="例：Hello World!"><?php echo htmlspecialchars($tweet); ?></textarea>
                    <input type="hidden" name="reply_tweet_id" value="<?php echo htmlspecialchars($_REQUEST['res']); ?>">
                <?php else: ?>
                    <textarea name="tweet" cols="50" rows="5" class="form-control" placeholder="例：Hello World!"></textarea>
                <?php endif; ?>
              </div>
            </div>

          <input type="submit" class="btn btn-info" value="つぶやく">
        </form>
      </div>

      <div class="col-md-8 content-margin-top">
        
        <?php while ($tweet = mysqli_fetch_assoc($tweets)): ?>
            <div class="msg">
              <img src="member_picture/<?php echo htmlspecialchars($tweet['picture_path']); ?>" width="48" height="48">
              <p>
                <?php echo htmlspecialchars($tweet['tweet']); ?><span class="name"> (<?php echo htmlspecialchars($tweet['nick_name']); ?>) </span>
                [<a href="index.php?res=<?php echo htmlspecialchars($tweet['tweet_id']) ?>">Re</a>]
              </p>
              <p class="day">
                <a href="view.php?id=<?php echo htmlspecialchars($tweet['tweet_id']); ?>">
                  <?php echo htmlspecialchars($tweet['created']); ?>
                </a>
                <?php if ($tweet['reply_tweet_id']): ?>
                    <a href="view.php?id=<?php echo htmlspecialchars($tweet['reply_tweet_id']); ?>">返信元のつぶやきへ</a>
                <?php endif; ?>
                [<a href="#" style="color: #F33;">削除</a>]
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
