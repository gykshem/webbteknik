<?php
/*
 * Enkelt dokument med en aning JavaScript
 *
 * Skall användas i kapitel 1
 *
 * Eleven behöver inte ännu förstå hur skriptet fungerar, utan det är en demonstartion
 * av de tre olika teknikernas ROLLER som är avsedd.
 */
?>
<!DOCTYPE html>
<html lang="sv">
  <head>
    <meta charset="utf-8" />
    <title>Enkelt HTML dokument med en aning CSS (kodexempel 1a)</title>
    <style>
      body {
          font-family: sans-serif;
          width: 500px;
          margin: auto;
          background-color: navajowhite;
      }
      h1 {
          text-align: center;
          color: brown;
          text-shadow: 2px 2px 1px white;
      }
      span {
          font-style: italic;
      }
      a {
          text-decoration: none;
          padding: 3px;
          color: black;
          border: 2px outset;
          background-color: lightgrey;
      }
    </style>
  </head>
  <body>
    <h1>Enkelt HTML dokument med en aning CSS</h1>
    <p>
      <a href="#klick" role="button">Utvidga rubriken</a>
    </p>
    <script>
      document.getElementsByTagName("a")[0].onclick = function () {
          var rubrik = document.getElementsByTagName("h1")[0];
          var rubtext = rubrik.innerHTML;
          rubrik.innerHTML = rubtext + " och lite JavaScript";
          return false;
      }
    </script>
  </body>
</html>