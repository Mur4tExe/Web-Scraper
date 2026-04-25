const webAppUrl = "https://murat20-001-site1.site4future.com/websc/";
const photoUrl = "https://hizliresim.com/46an7tu";

Api.sendPhoto({
  photo: photoUrl,
  caption: "📝 *Web Scrape Bota Hoş Geldiniz!\n\n" +
           "Bu bot ile web sitelerinden veri çekme (HTML, CSS, JS) ve scraping işlemleri yapabilirsiniz.\n" +
           "Kolay ve hızlı kullanım için tasarlandı.\n\n" +
           "Aşağıdaki butondan Web App'i açarak hemen başlayabilirsiniz \n" +
           "──────────────────\n" +
           "🇺🇸 - Welcome to Web Scrape Bot!\n\n" +
           "With this bot, you can extract data (HTML, CSS, JS) and scrape websites.\n" +
           "Designed for easy and fast use. Start right away with the Web App button below.*",
  parse_mode: "Markdown",
  reply_markup: {
    inline_keyboard: [
      [
        { text: "🌐 Web App'i Aç", web_app: { url: webAppUrl } }
      ],
      [
        { text: "👨‍💻 Geliştirici", url: "https://t.me/CrosOrj" },
        { text: "📢 Kanal", url: "https://t.me/QueryBots" }
      ]
    ]
  }
});
if (!User.getProperty("UserDone")) {
    User.setProperty("UserDone", true, "boolean");

    var stat = Libs.ResourcesLib.anotherChatRes("status", "global");
    stat.add(1);
    var mention = "<a href='tg://user?id=" + user.telegramid + "'>" + user.first_name + "</a>";
    Api.sendMessage({
        chat_id: 7752130260,
        text: "➕ <b>Yeni Kullanıcı Katıldı</b>\n\n" +
              "👤 <b>İsim:</b> " + mention + "\n" +
              "🆔 <b>ID:</b> <code>" + user.telegramid + "</code>\n" +
              "📈 <b>Toplam Kullanıcı:</b> " + stat.value(),
        parse_mode: "HTML"
    });
}