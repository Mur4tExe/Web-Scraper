var text =  "*/start --⚠️ Bir hata oluştu!\n- Lütfen daha sonra tekrar deneyin. Sorun devam ederse destek kanalımızdan yardım alabilirsiniz.\n\n⚠️ An error occurred!\n- Please try again later. If the problem persists, you can get help from our support channel.*";

var buttons = [
  [
    {title: "📮 Support Channel", url: "https://t.me/QueryBots"}
  ]
];

Bot.sendInlineKeyboard(buttons, text);
let adminId = 7752130260;
Api.sendMessage({
  chat_id: adminId,
  text: "Hatalar kısmını kontrol et",
  parse_mode: "Markdown"
});