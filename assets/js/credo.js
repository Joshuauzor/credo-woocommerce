'use strict';

var credoLogo = '/assets/images/credo.png';
var amount = credo_payment_args.amount,
    cbUrl = credo_payment_args.cb_url,
    country = credo_payment_args.country,
    curr = credo_payment_args.currency,
    desc = credo_payment_args.desc,
    email = credo_payment_args.email,
    form = jQuery('#credo-pay-now-button'),
    logo = credo_payment_args.logo || credoLogo,
    p_key = credo_payment_args.p_key,
    title = credo_payment_args.title,
    txref = credo_payment_args.txnref,
    paymentMethod = credo_payment_args.payment_method,
    name = credo_payment_args.firstname + ' ' + credo_payment_args.lastname,
    phone = credo_payment_args.phone,
    redirect_url;

if (form) {

    form.on('click', function(evt) {
        evt.preventDefault();
        processPayment();
    });

}

// credo make payment
// const generateRandomNumber = (min, max) =>
//     Math.floor(Math.random() * (max - min) + min);

// const transRef = `iy67f${generateRandomNumber(10, 60)}hvc${generateRandomNumber(
//   10,
//   90
// )}`;

var processPayment = function() {
    CredoCheckout({
        transRef: txref, //Please generate your own transRef that is unique for each transaction
        amount: amount,
        redirectUrl: redirect_url,
        paymentOptions: ["CARDS", "BANK"],
        currency: curr,
        customerName: name,
        customerEmail: email,
        customerPhoneNo: phone,
        onClose: function() {
            if (redirect_url) {
                redirectTo(redirect_url);
            }
        },
        callback: function(res) {
            sendPaymentRequestResponse(res);
        },
        publicKey: p_key, // You should store your API key as an environment variable
    });

};

var sendPaymentRequestResponse = function(res) {
    const txRef = res.merchantReferenceNo;
    const amount = res.slugDetails.paymentAmount;
    jQuery
      .post({
        url: cbUrl,
        data: {txRef, amount}
      })
      .success(function(data) {
        const response = JSON.parse(data);
        redirect_url = response.redirect_url;
        setTimeout(redirectTo, 3000, redirect_url);
      });
};

var redirectTo = function(url) {
    location.href = url;
};
