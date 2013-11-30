<?php

require_once("../../phplib/util.php");
util_assertNotMirror();
util_assertNotLoggedIn();

$submitButton = util_getRequestParameter('submitButton');
$identity = util_getRequestParameter('identity');
$email = util_getRequestParameter('email');

SmartyWrap::assign('identity', $identity);
SmartyWrap::assign('email', $email);
SmartyWrap::assign('page_title', 'Parolă uitată');
SmartyWrap::assign('suggestHiddenSearchForm', true);

if ($submitButton) {
  if (!$email) {
    FlashMessage::add('Trebuie să introduceți o adresă de e-mail.');
    SmartyWrap::displayCommonPageWithSkin('auth/parola-uitata.ihtml');
  } else {
    $user = User::get_by_email($email);
    if ($user) {
      log_userLog("Password recovery requested for $email from " . $_SERVER['REMOTE_ADDR']);

      // Create the token
      $pt = Model::factory('PasswordToken')->create();
      $pt->userId = $user->id;
      $pt->token = util_randomCapitalLetterString(20);
      $pt->save();

      // Send email
      SmartyWrap::assign('homePage', util_getFullServerUrl());
      SmartyWrap::assign('token', $pt->token);
      $body = SmartyWrap::fetch('email/resetPassword.ihtml');
      $ourEmail = Config::get('global.contact');
      $headers = array("From: DEX online <$ourEmail>", "Reply-To: $ourEmail", 'Content-Type: text/plain; charset=UTF-8');
      $result = mail($email, "Schimbarea parolei pentru DEX online", $body, implode("\r\n", $headers));

      // Display a confirmation even for incorrect addresses.
      SmartyWrap::displayCommonPageWithSkin('auth/passwordRecoveryEmailSent.ihtml');
    } else {
      FlashMessage::add('Nu există niciun utilizator cu e-mailul introdus.');
      SmartyWrap::displayCommonPageWithSkin('auth/parola-uitata.ihtml');
    }
  }
} else {
  SmartyWrap::displayCommonPageWithSkin('auth/parola-uitata.ihtml');
}


?>
