$(function () {
  $('.pvr_chat_button, .pvr_chat_wrapper .close_chat').on('click', function () {
    $('.pvr_chat_wrapper').toggleClass('active');
    // 添加您的AJAX代码
    $('.chat-calls .call.self').remove();
    $('.chat-calls .call.callai').remove();
    return false;
  });

$('.chat-controls').on('keypress click', '.call-input, .ai_box_btn', function (e) {
    if ((e.type === 'keypress' && e.keyCode === 13) || (e.type === 'click' && $(this).hasClass('ai_box_btn'))) {
      var val = $('.call-input').val(); // 获取输入框的值
       
      if (val === '') {
        alert('输入内容不能为空');
        return false;
      }
      
      // Append user input to chat-calls
      $('.chat-calls').append('<div class="call self"><div class="call-content">' + val + '</div></div>');
      $('.call-input').val('');

      // Retrieve user dialog
      var contentArray = [];
      var $chatRightP = $(".self .call-content");
      if ($chatRightP.length > 0) {
        $chatRightP.each(function () {
          var content = $(this).text();
          contentArray.push(content);
        });
      }
      console.log(contentArray); // Array of user dialog

      // Retrieve AI dialog
      var aicontentArray = [];
      var $chatLeftP = $(".callai .call-content");
      if ($chatLeftP.length > 0) {
        $chatLeftP.each(function () {
          var aicontent = $(this).text().trim();
          if (aicontent !== "") {
            aicontentArray.push(aicontent);
          }
        });
      }
      console.log(aicontentArray); // Array of AI dialog

      var usercon = JSON.stringify(contentArray);
      var aicon = JSON.stringify(aicontentArray);

      var currentProtocol = window.location.protocol;
      var currentDomain = window.location.hostname;
      var url = currentProtocol + "//" + currentDomain + "/Deng/chattion";

      $('.chat-calls').append('<div class="call aiload"><div class="call-content"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...</div></div>');

      $.ajax({
        url: url,
        type: "POST",
        data: {
          usercon: usercon,
          aicon: aicon,
          newcon: val
        },
        async: true,
        success: function (data) {
          var des = JSON.stringify(data);
          $('.chat-calls').append('<div class="call callai"><div class="call-content">' + data + '</div></div>');

          $('.chat-calls .call.aiload').remove();

          var $calls_w = $('.pvr_chat_wrapper .chat-calls');
          $calls_w.scrollTop($calls_w.prop("scrollHeight"));
          $calls_w.perfectScrollbar('update');
        },
        error: function (err) {
          console.log('错误' + err);
        }
      });

      var $calls_w = $('.pvr_chat_wrapper .chat-calls');
      $calls_w.scrollTop($calls_w.prop("scrollHeight"));
      $calls_w.perfectScrollbar('update');
      return false;
    }
  });

  $('.pvr_chat_wrapper .chat-calls').perfectScrollbar();
});