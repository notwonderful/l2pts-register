<?php

function getDatabaseConnection(): PDO|PDOException
{
    $server = "127.0.0.1";
    $database = "lin2db";
    $user = "notwonderful";
    $password = "Elvis";

    try {
        $conn = new PDO("odbc:Driver={ODBC Driver 17 for SQL Server};Server=$server;Database=$database", $user, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $conn;
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

function validateInput(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function checkStr(string $str): bool
{
    $pattern = '/^[a-zA-Z0-9]+$/';

    if (preg_match($pattern, $str)) {
        return true;
    }

    return false;
}

function checkStrLength(string $str): bool
{
    $len = strlen($str);

    return $len >= 4 && $len <= 16;
}

function encrypt(string $str): string
{
    $key = array_fill(0, 16, 0);
    $dst = array_fill(0, 16, 0);

    $nBytes = strlen($str);

    for ($i = 0; $i < $nBytes; $i++) {
        $key[$i] = ord($str[$i]);
        $dst[$i] = $key[$i];
    }

    for ($i = 0; $i < 16; $i += 4) {
        $rslt = $key[$i] + ($key[$i+1] << 8) + ($key[$i+2] << 16) + ($key[$i+3] << 24);
        $multiplier = [213119, 213247, 213203, 213821][$i / 4];
        $adder = [2529077, 2529089, 2529589, 2529997][$i / 4];

        $calc = ($rslt * $multiplier + $adder) % 4294967296;

        $key[$i] = $calc & 0xFF;
        $key[$i+1] = ($calc >> 8) & 0xFF;
        $key[$i+2] = ($calc >> 16) & 0xFF;
        $key[$i+3] = ($calc >> 24) & 0xFF;
    }

    $dst[0] = $dst[0] ^ $key[0];
    for ($i = 1; $i < 16; $i++) {
        $dst[$i] = $dst[$i] ^ $dst[$i-1] ^ $key[$i];
        if ($dst[$i] == 0) {
            $dst[$i] = 102;
        }
    }

    $binaryData = "";
    for ($i = 0; $i < 16; $i++) {
        $binaryData .= chr($dst[$i]);
    }

    return $binaryData;
}