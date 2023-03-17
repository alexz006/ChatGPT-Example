<?php
include "openai_chat_api.php";

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/default.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/marked@3.0.7/marked.min.js"></script>
  <title>ChatGPT</title>
  <style>
    .selected{
      font-weight: bold
    }
  </style>
</head>
<body>
  <div class="container">
    <h4 class="text-center">
    <small class="h5 font-weight-normal"><a href="/" <?= empty($_GET['type']) ? 'class="selected"' : '' ?>>API GPT-3</a> | <a href="/?type=turbo" <?= $_GET['type']=='turbo' ? 'class="selected"' : '' ?>>API GPT-3.5 turbo</a> | <a href="/?type=chat"<?= $_GET['type']=='chat' ? 'class="selected"' : '' ?>>Not an API (chat.openai.com)</a></small>
  </h4>
    <form method="post">
    <div class="row form-group" <?= $_GET['type']=='chat' ? '' : 'style="display:none"' ?>>
      <div class="col-9">
        <input type="text" class="form-control" id="conversationId" placeholder="set chat id or leave blank to start a new chat" value="">
      </div>
      <div class="col-3">
        <select class="form-control" id="chatModel">
        <option value="text-davinci-002-render-sha">Default (GPT-3.5)</option>
        <option value="text-davinci-002-render-paid">Legacy (GPT-3.5)</option>
        <option value="gpt-4">GPT-4</option>
        </select>
      </div>
    </div>
    <div class="row"> 
    <div class="messages col-12" id="messages">
      <!-- Messages will be displayed here -->
    </div>
      </div>
      <div class="row">
        <div class="form-group col-9">
          <textarea rows="1" class="form-control" id="messageInput" placeholder="Enter a message"></textarea>
        </div>
        <div class="col-3">
          <button type="submit" class="btn btn-primary w-100" id="sendMessage">
            Send
          </button>
        </div>
      </div>
    </form>
  </div>
<script>
  // Get the input field and submit button
  const conversationId = document.getElementById("conversationId");
  const messageInput = document.getElementById("messageInput");
  const sendMessage = document.getElementById("sendMessage");
  const messages = document.getElementById("messages");

  var parent_message_id = '';
  
  let сookie = getCookie('conversation_id');
  conversationId.value = сookie ?? '';

  messageInput.focus();

  // Send message when submit button is clicked
  function sendMessageHandler(event) {
    if (event.type != "click" && !(event.type == "keydown" && event.ctrlKey && event.keyCode == 13))
      return;
    event.preventDefault();

    // Get the message from the input field
    const message = messageInput.value;
    if (!message.trim()) {
      messageInput.focus();
      return;
    }

    const conversation_id = conversationId.value;

    sendMessage.innerText = 'loading...';
    conversationId.disabled = true;
    sendMessage.disabled = true;
    messageInput.disabled = true;

    // Create a new message element
    const messageElement = document.createElement("div");
    messageElement.innerHTML = '<p><b>You</b>:<br>' + replaceHTML(message) + '</p>';
    messages.appendChild(document.createElement("hr"));
    messages.appendChild(messageElement);

    // Clear the input field
    messageInput.value = "";
    messageInputResize();

    // Send the message to the server
    fetch('', {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          <?= $_GET['type']=='chat' ? "openai_type: 'chat'," : '' ?>
          <?= $_GET['type']=='turbo' ? "openai_type: 'turbo'," : '' ?>
          message: message,
          chat_model: document.getElementById("chatModel").value,
          conversation_id: conversation_id,
          parent_message_id: parent_message_id
        }),
      })
      .then((response) => response.json())
      .then((data) => {

        sendMessage.innerText = 'Send';
        sendMessage.disabled = false;
        messageInput.disabled = false;
        messageInput.focus();

        // Display the response from the server
        const messageElement = document.createElement("div");
        const p = document.createElement("p");

        if (data.hasOwnProperty('error')) {
          p.innerHTML = '<b>AI</b>: ' + data.error.msg;
        } else if (data.hasOwnProperty('message')) {
          p.innerHTML = '<b>AI</b>: ' + marked(data.message);
          if (data.hasOwnProperty('conversation_id')) {
            parent_message_id = data.parent_message_id; // set parent_message_id
            conversationId.value = data.conversation_id; // set conversation_id
            setCookie("conversation_id", data.conversation_id, 365);
          }
        }
        messageElement.appendChild(p);
        messages.appendChild(document.createElement("hr"));
        messages.appendChild(messageElement);
        document.querySelectorAll("pre code").forEach((block) => {
          hljs.highlightBlock(block);
        });
        window.scrollTo(0, document.body.scrollHeight);
      })
      .catch(error => {
        console.error(error);
        sendMessage.innerText = 'Send';
        sendMessage.disabled = false;
        messageInput.disabled = false;
        messageInput.focus();
      });
  }

  sendMessage.addEventListener("click", sendMessageHandler);
  document.addEventListener("keydown", sendMessageHandler);

  /*--------------------*/

  function messageInputResize() {
    messageInput.style.height = "auto";
    messageInput.style.height = (messageInput.scrollHeight + 2) + "px";
  }
  messageInput.addEventListener("input", messageInputResize);

  function replaceHTML(str) {
    const jsEntities = [
      ['&', '&amp;'],
      ['<', '&lt;'],
      ['>', '&gt;'],
      ['\'', '&#39;'],
      ['"', '&quot;'],
      ['\n', '<br>'],
      ['\t', '&nbsp;&nbsp;']
    ];
    for (let i = 0; i < jsEntities.length; i++) {
      str = str.replace(new RegExp(jsEntities[i][0], 'g'), jsEntities[i][1]);
    }
    return str;
  }

  function setCookie(name, value, days) {
    let expires = "";
    if (days) {
      let date = new Date();
      date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
      expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/";
  }

  function getCookie(name) {
    let nameEQ = name + "=";
    let ca = document.cookie.split(';');
    for (let i = 0; i < ca.length; i++) {
      let c = ca[i];
      while (c.charAt(0) == ' ') c = c.substring(1, c.length);
      if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
  }
</script>

</body>
</html>
