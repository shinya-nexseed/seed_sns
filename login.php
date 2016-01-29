<?php 
    require('dbconnect.php');

    session_start();

    if (isset($_COOKIE['email'])) {
        if ($_COOKIE['email'] != '') {
            $_POST['email'] = $_COOKIE['email'];
            $_POST['password'] = $_COOKIE['password'];
            $_POST['save'] = 'on';
        }
    }

    if (!empty($_POST)) {
        // メールアドレスとパスワードを入力していればログイン処理をする
        if ($_POST['email'] != '' && $_POST['password'] != '') {

            // SELECT文のWHEREでmembersテーブルの中からメールアドレスとパスワードが共に一致するデータがあるかを検索し、その結果を$sqlに格納
            $sql = sprintf('SELECT * FROM members WHERE email="%s" AND password="%s"',
                mysqli_real_escape_string($db, $_POST['email']),
                mysqli_real_escape_string($db, sha1($_POST['password']))
                // パスワードの比較はINSERTした時と同様sha1()で暗号化したもので
            );

            // クエリ関数にかけた結果を$recordに格納
            $record = mysqli_query($db, $sql) or die(mysqli_error($db));

            // $recordに格納されているデータを一件ずつ$tableに取り出してif文の中の処理を実行
            // この時、メンバーは被らないデータとしてひとつしか存在しないので、$recordの中にも一致するひとつのメンバーのデータが入る
            if ($table = mysqli_fetch_assoc($record)) {
                // ログイン成功
                
                // メンバーのidをセッションに保存
                $_SESSION['member_id'] = $table['member_id'];

                // ログインした時間をセッションに保存
                $_SESSION['time'] = time();

                // ログイン情報を記録する
                if ($_POST['save'] == 'on') {
                    setcookie('email', $_POST['email'], time()+60*60*24*14);
                    setcookie('password', $_POST['password'], time()+60*60*24*14);
                }

                header('Location: index.php');
                exit(); 
            } else {
                $error['login'] = 'failed';
            }

        } else {
            $error['login'] = 'blank';
        }
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
      <div class="col-md-6 col-md-offset-3 content-margin-top">
        <legend>ログイン</legend>
        <form method="post" action="" class="form-horizontal" role="form">
          <!-- メールアドレス -->
          <div class="form-group">
            <label class="col-sm-4 control-label">メールアドレス</label>
            <div class="col-sm-8">
              <?php if (isset($_POST['email'])): ?>
                  <!-- join/index.phpに時とは別の書き方 (sprintf()を使わない) -->
                  <input type="email" name="email" class="form-control" placeholder="例： seed@nex.com" value="<?php echo htmlspecialchars($_POST['email']); ?>">
              <?php else: ?>
                  <input type="email" name="email" class="form-control" placeholder="例： seed@nex.com">
              <?php endif; ?>
              <?php if (isset($error)): ?>
                  <?php if ($error['login'] == 'blank'): ?>
                      <p class="error">* メールアドレスとパスワードをご記入ください。</p>
                  <?php endif; ?>
                  <?php if ($error['login'] == 'failed'): ?>
                      <p class="error">* ログインに失敗しました。正しく情報をご記入ください。</p>
                  <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
          <!-- パスワード -->
          <div class="form-group">
            <label class="col-sm-4 control-label">パスワード</label>
            <div class="col-sm-8">
              <?php if (isset($_POST['password'])): ?>
                  <input type="password" name="password" class="form-control" placeholder="" value="<?php echo htmlspecialchars($_POST['password']); ?>">
              <?php else: ?>
                  <input type="password" name="password" class="form-control" placeholder="">
              <?php endif; ?>
            </div>
          </div>
          <!-- 自動ログイン -->
          <div class="form-group">
            <label class="col-sm-4 control-label">自動ログイン</label>
            <div class="col-sm-8">
              <input type="checkbox" id="save" name="save" value="on">
            </div>
          </div>
          <input type="submit" class="btn btn-info" value="ログイン"> | <a href="join/" class="btn btn-default">&raquo; 会員登録</a>
        </form>
      </div>
    </div>
  </div>

    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
  </body>
</html>
