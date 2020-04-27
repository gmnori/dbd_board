<?php

//変数初期化
$now_date = null;
$data = null;
$message = array();
$error_message = array();
$discord = null;
$discord_user_id = null;
$discord_user_link = null;
$suv_rank = null;
$comment = null;
$success_message = null;
$articles = null;
$statement = null;
$start = null;
$clean = array();
$parameters = null;

require('dbconnect.php');
date_default_timezone_set('Asia/Tokyo');

//投稿あり
if( !empty($_POST['send'])){
    // エラーチェック、サニタイズ
    if(empty($_POST['discord'])){
        $error_message[] = 'Discord IDを入力してください。(例：DbD太郎#1234)';
    } else {
        $clean['discord'] = htmlspecialchars($_POST['discord'], ENT_QUOTES);
        $clean['discord'] = preg_replace('/\\r\\n|\\n|\\r/', '', $clean['discord']);
    }
    if(empty($_POST['suv_rank'])){
        $error_message[] = '生存者ランクを入力してください。';
    } else {
        $clean['suv_rank'] = htmlspecialchars($_POST['suv_rank'], ENT_QUOTES);
        $clean['suv_rank'] = preg_replace('/\\r\\n|\\n|\\r/', '', $clean['suv_rank']);
    }
    if(empty($_POST['comment'])){
        $error_message[] = 'コメントを入力してください。';
    } else {
        $clean['comment'] = htmlspecialchars($_POST['comment'], ENT_QUOTES);
        $clean['comment'] = preg_replace('/\\r\\n|\\n|\\r/', '', $clean['comment']);
    }
    // 投稿
    if(empty($error_message)){
        $now_date = date("Y-m-d H:i:s");
        $statement = $db->prepare('INSERT INTO articles (discord, discord_id, suv_rank, comment, posted_at) values (?,?,?,?,?)');
        $statement->execute(array($_POST['discord'], $_POST['discord_user_id'], $_POST['suv_rank'], $_POST['comment'], $now_date));
        $success_message = '投稿しました！投稿は3時間経過で自動的に非表示となります。';
    }
}

// 表示
// 10件ごとにページネーションする場合のオプション
// if (isset($_REQUEST['page']) && is_numeric($_REQUEST['page'])) {
//     $page = $_REQUEST['page'];
// } else {
//     $page = 1;
// }

// $start = 10 * ($page - 1);

$articles = $db->prepare('SELECT * FROM articles WHERE NOW() < posted_at + interval 3 hour GROUP BY discord ORDER BY id DESC');
$articles->bindParam(1, $start, PDO::PARAM_INT);
$articles->execute();

?>

<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="Refresh" content="60">
        <title>Dead by Daylight VC募集掲示板</title>
        <link rel="stylesheet" href="./css/index.css">
    <body>
        <header>
            <h1>Dead by Daylight VC募集掲示板</h1>
        </header>
        <section class="form">
            <div>※Steamフレンドコードのやり取りはDiscord上で行なってください。</div>
            <div>※投稿は3時間経過で自動的に非表示となります。</div><br>
            <?php echo $parameters ?>
                <!-- 投稿成功時メッセージ -->
                <?php if (!empty($success_message)): ?>
                    <p class="success_message"><?php echo $success_message; ?></p>
                <?php endif; ?>
                <!-- 投稿失敗時メッセージ -->
                <?php if(!empty($error_message)): ?>
                    <ul class="error_message">
                        <?php foreach($error_message as $value): ?>
                            <li>☆<?php echo $value; ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <!-- フォーム -->
                <form method="post" action="" autocomplete="off">
                    <div id="discord_box">
                        <label for="discord">Discord ID：(例: DBDやる夫#1234)</label>
                        <input type="text" name="discord" value="<?php echo $discord; ?>" >
                    </div>
                    <div id="discord_user_id_box">
                        <label for="discord_user_id">Discord ユーザーID(18桁の数字) (入力無くてもOK、あるとリンクが自動生成されます)</label>
                        <input type="number" name="discord_user_id" value="<?php echo $discord_user_id; ?>">
                    </div>
                    <div id="suv_rank_box">
                        <label for="suv_rank">生存者ランク: </label>
                        <input type="number" name="suv_rank" value="<?php echo $suv_rank; ?>">
                    </div>
                    <div id="comment_box">
                        <label for="comment">コメント、遊びたい時間帯：</label>
                        <textarea id="comment" name="comment" max_length="50" value="<?php echo $comment; ?>"></textarea>
                    </div>
                    <input type="submit" name="send" value="送信する" >
                </form>
        </section>
        <section class="container">
            <!-- コンテナ -->
            <?php while($article = $articles->fetch()): ?>    
                <article>
                    <table class="info">
                        <tr class="note" id="discord_id">
                            <th><h2>Discord ID: </h2></th>
                            <td><?php echo $article['discord']; ?></td>
                            <?php if(!empty($article['discord_id'])): ?>
                                <?php $discord_user_link = 'https://discordapp.com/users/' . $article['discord_id'] ?>
                                <td id="join_button" rowspan="2"><input class="join" type="submit" name="join_discord" onclick="window.open('<?php echo $discord_user_link ?>')" value="連絡する" ></td>
                            <?php endif; ?>
                        </tr>
                        <tr class="note" id="suv_rank">
                            <th><h2>生存者ランク: </h2></th>
                            <td><?php echo $article['suv_rank']; ?></td>
                        </tr>
                        <tr class="note" id="comment">
                            <th><h2>コメント: </h2></th>
                            <td><?php echo $article['comment']; ?></td>
                        </tr>
                        <tr>
                            <td rowspan="2" align="center"><time><?php echo date('Y年m月d日 H:i', strtotime($article['posted_at'])); ?></time></td>
                        </tr>
                    </table>
                </article>
            <?php endwhile; ?>
        </section>
        <section class="footer">
            <div class="to_admin">管理人への連絡は<a href="https://twitter.com/gmnoir" name="gmnoir Twitter" target=_blank>こちら</a></div>
        </section>
    </body>
</html>