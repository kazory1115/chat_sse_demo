<?php
// index.php
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>簡易 SSE 聊天室</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        body { font-family: sans-serif; margin: 20px; }
        #chat-box {
            border: 1px solid #ccc;
            height: 300px;
            overflow-y: auto;
            padding: 10px;
            margin-bottom: 10px;
            background: #fafafa;
        }
        .msg { margin-bottom: 5px; }
        .msg .user { font-weight: bold; margin-right: 6px; }
        .msg .time { color: #999; font-size: 12px; margin-left: 6px; }
        #username { width: 120px; }
        #message { width: 300px; }
        #send-btn { padding: 5px 10px; }
    </style>
</head>
<body>

<h2>簡易 SSE 聊天室（PHP + jQuery）</h2>

<div id="chat-box"></div>

<div style="margin: 8px 0; color: #555;">
    伺服器時間戳：<span id="server-ts">-</span>
</div>

<div>
    暱稱：
    <input type="text" id="username" placeholder="你的名字" value="User<?php echo rand(100,999); ?>">
</div>
<div style="margin-top: 5px;">
    訊息：
    <input type="text" id="message" placeholder="輸入訊息..." />
    <button id="send-btn">送出</button>
</div>

<script>
$(function () {
    // ====== 1. 送出訊息（用 jQuery AJAX） ======
    $('#send-btn').on('click', sendMessage);
    $('#message').on('keydown', function (e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });

    function sendMessage() {
        const user = $('#username').val().trim();
        const msg  = $('#message').val().trim();

        if (!user) {
            alert('請輸入暱稱');
            return;
        }
        if (!msg) return;

        $.ajax({
            url: 'send.php',
            method: 'POST',
            data: { user: user, message: msg },
            success: function (res) {
                $('#message').val('');
            },
            error: function () {
                alert('送出失敗，請稍後再試');
            }
        });
    }

    // ====== 2. 接收訊息（用原生 EventSource + SSE） ======
    if (!!window.EventSource) {
        const source = new EventSource('stream.php');

        source.onmessage = function (event) {
            // event.data 是後端推來的 JSON 字串
            try {
                const msg = JSON.parse(event.data);
                appendMessage(msg);
            } catch (e) {
                console.error('解析訊息失敗', e, event.data);
            }
        };

        source.onerror = function (err) {
            console.error('SSE 連線錯誤：', err);
            // 這邊先不關閉，讓瀏覽器自動重連
        };
    } else {
        alert('你的瀏覽器不支援 EventSource / SSE');
    }

    function appendMessage(msg) {
        const $box = $('#chat-box');
        const safeUser = $('<span>').text(msg.user).html();
        const safeText = $('<span>').text(msg.message).html();
        const safeTime = $('<span>').text(msg.created_at || '').html();

        const html = `
            <div class="msg">
                <span class="user">${safeUser}：</span>
                <span class="text">${safeText}</span>
                <span class="time">${safeTime}</span>
            </div>
        `;
        $box.append(html);
        $box.scrollTop($box[0].scrollHeight);
    }

    // ====== 3. 輪詢時間戳 ======
    function fetchServerTime() {
        $.ajax({
            url: 'time.php',
            method: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res && res.timestamp) {
                    $('#server-ts').text(res.timestamp);
                } else {
                    $('#server-ts').text('-');
                }
            },
            error: function () {
                $('#server-ts').text('-');
            }
        });
    }

    fetchServerTime();
    setInterval(fetchServerTime, 3000);
});
</script>

</body>
</html>
