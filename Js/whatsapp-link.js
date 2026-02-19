(function () {
  var phone = "351966545510";
  var isMobile = /Android|iPhone|iPad|iPod|Windows Phone/i.test(navigator.userAgent);
  var whatsappLink = document.getElementById("whatsapp-link");
  var supportPhoneLink = document.getElementById("support-phone-link");

  if (whatsappLink) {
    whatsappLink.href = isMobile
      ? "https://wa.me/" + phone
      : "https://web.whatsapp.com/send?phone=" + phone;
  }

  if (supportPhoneLink) {
    supportPhoneLink.href = isMobile
      ? "tel:+" + phone
      : "https://web.whatsapp.com/send?phone=" + phone;
  }
})();
