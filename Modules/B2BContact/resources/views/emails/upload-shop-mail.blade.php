<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Cansativa Email</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f7f8fa; margin: 0; padding: 0;">
  <div style="max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; border: 1px solid #ddd; padding: 20px; box-shadow: 0 2px 6px rgba(0,0,0,0.05);">
    
    <!-- Header image -->
    <div style="text-align: center; margin-bottom: 15px;">
      <img src="https://cns-shop-web.kemang.sg/_nuxt/cansativa-logo.DJ7hB6Sk.png" alt="Cansativa" style="max-width: 200px; height: auto;">
    </div>

    <p>Hallo,</p>
    <p>Eine Apotheke hat soeben eine Kundenliste über den Cansativa Shop geteilt:</p>

    <p style="font-weight: bold; margin-top: 15px;">📋 Apothekendaten</p>
    <ul style="padding-left: 20px; margin-top: 5px;">
      <li>Name der Apotheke: {{$data['pharmacy_name']}}</li>
      <li>Email: {{$data['pharmacy_email']}}</li>
      <li>Telefon: {{$data['pharmacy_phone']}}</li>
      <li>Betäubungsmittelnummer: {{$data['narcotics_number']}}</li>
      <li>Adresse: {{$data['country']}}, {{$data['street']}}, {{$data['postal_code']}}, {{$data['city']}}</li>
    </ul>

    <p style="font-weight: bold; margin-top: 15px;">📁 Hochgeladene Datei</p>
    <ul style="padding-left: 20px; margin-top: 5px;">
      <li>Dateiname: {{ $data['file_name'] }}</li>
      <li>Datum: {{ $data['upload_date'] }}</li>
    </ul>

    <!-- Centered footer -->
    <div style="margin-top: 30px; font-size: 14px; text-align: center;">
      Wenn Sie Fragen haben oder weitere Hilfe benötigen,<br>
      können Sie sich gerne an unser Support-Team unter 
      <a href="mailto:support@cansativa.de" style="color: #000; font-weight: bold; text-decoration: none;">support@cansativa.de</a> wenden.
    </div>

    <p style="margin-top: 20px; text-align: center;">Das Cansativa-Team</p>
  </div>
</body>
</html>
