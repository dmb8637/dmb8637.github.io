<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>What are HTML imports and how do they work</title>
    <link rel="import" href="/anketa_D_A_Bokov.htm">
  </head>
  <body>
    <h1>Сайт-резюме</h1>
 
    <script>
      var link = document.querySelector('link[rel=import]');
      var content = link.import.querySelector('#anketa_D_A_Bokov-dm');
      document.body.appendChild(content.cloneNode(true));
    </script>
 
  </body>
</html>
