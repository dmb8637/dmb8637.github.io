<?php
// ������ ������ �� PDF
// ������ 0.3
// �����: ������� ������ a.k.a Ramon
// E-mail: alex@rembish.ru
// Copyright 2009
function decodeAsciiHex($input) {
    $output = "";
    $isOdd = true;
    $isComment = false;
    for($i = 0, $codeHigh = -1; $i < strlen($input) && $input[$i] != '>'; $i++) {
        $c = $input[$i];
        if($isComment) {
            if ($c == '\r' || $c == '\n')
                $isComment = false;
            continue;
        }
        switch($c) {
            case '\0': case '\t': case '\r': case '\f': case '\n': case ' ': break;
            case '%': 
                $isComment = true;
            break;
            default:
                $code = hexdec($c);
                if($code === 0 && $c != '0')
                    return "";
                if($isOdd)
                    $codeHigh = $code;
                else
                    $output .= chr($codeHigh * 16 + $code);
                $isOdd = !$isOdd;
            break;
        }
    }
    if($input[$i] != '>')
        return "";
    if($isOdd)
        $output .= chr($codeHigh * 16);
    return $output;
}
function decodeAscii85($input) {
    $output = "";
    $isComment = false;
    $ords = array();
    
    for($i = 0, $state = 0; $i < strlen($input) && $input[$i] != '~'; $i++) {
        $c = $input[$i];
        if($isComment) {
            if ($c == '\r' || $c == '\n')
                $isComment = false;
            continue;
        }
        if ($c == '\0' || $c == '\t' || $c == '\r' || $c == '\f' || $c == '\n' || $c == ' ')
            continue;
        if ($c == '%') {
            $isComment = true;
            continue;
        }
        if ($c == 'z' && $state === 0) {
            $output .= str_repeat(chr(0), 4);
            continue;
        }
        if ($c < '!' || $c > 'u')
            return "";
        $code = ord($input[$i]) & 0xff;
        $ords[$state++] = $code - ord('!');
        if ($state == 5) {
            $state = 0;
            for ($sum = 0, $j = 0; $j < 5; $j++)
                $sum = $sum * 85 + $ords[$j];
            for ($j = 3; $j >= 0; $j--)
                $output .= chr($sum >> ($j * 8));
        }
    }
    if ($state === 1)
        return "";
    elseif ($state > 1) {
        for ($i = 0, $sum = 0; $i < $state; $i++)
            $sum += ($ords[$i] + ($i == $state - 1)) * pow(85, 4 - $i);
        for ($i = 0; $i < $state - 1; $i++)
            $output .= chr($sum >> ((3 - $i) * 8));
    }
    return $output;
}
function decodeFlate($input) {
    // �������� ������ ��� ������ ������ ������ � PDF.
    // ����� ������ ����������� ������������ ���������.
    return @gzuncompress($input);
}
function getObjectOptions($object) {
    // ��� ����� �������� ��������� ��������� �������. ���������
    // ������� ����� ������ << � >>. ������ ����� ���������� �� ����� /.
    $options = array();
    if (preg_match("#<<(.*)>>#ismU", $object, $options)) {
        // �������� ����� ���� �� ����� �� /. ������ ������ ������� �� �������.
        $options = explode("/", $options[1]);
        @array_shift($options);
        // ����� �������� ������� ��� �������� ������������� ������
        // ������� �������� �������. ��������� ���� "/Option N" �������
        // � ���, ��� "Option" => N, �������� ���� "/Param", ���
        // "Param" => true.
        $o = array();
        for ($j = 0; $j < @count($options); $j++) {
            $options[$j] = preg_replace("#\s+#", " ", trim($options[$j]));
            if (strpos($options[$j], " ") !== false) {
                $parts = explode(" ", $options[$j], 2);
                $o[$parts[0]] = $parts[1];
            } else
                $o[$options[$j]] = true;
        }
        $options = $o;
        unset($o);
    }
    // ��������� ������ ��������� ����������.
    return $options;
}
function getDecodedStream($stream, $options) {
    // ����, ����� ���� �����, �������� ������������ �����-������
    // ������� ������, � �� � �����������. ��������� ������������.
    $data = "";
    // ���� � �������� ������ ���� �������� Filter, �� �� �����
    // ���� ��� ����������. �����, ������ ���������� ����������
    // ������ �����.
    if (empty($options["Filter"]))
        $data = $stream;
    else {
        // ���� � ������ ���� ����� ������ ������, �� ��� ����� �������� ������
        // �� �������� �����, � �� �� ������������ �� ������ ��� ��� �����
        // ���� ��������.
        $length = !empty($options["Length"]) && strpos($options["Length"], " ") === false ? $options["Length"] : strlen($stream);
        $_stream = substr($stream, 0, $length);
        // ���������� ����� �� ������� ������� �������� �� ������ ������ � �������
        // ������. PDF ������������ ����� ����� � �������, �� ����� ���������� �����
        // ����������: ASCII Hex, ASCII 85-base � GZ/Deflate. ���� ���������������
        // ����� � ��������� ��������������� ������� ��� ��������. ���� ��� �������
        // Crypt, �� ������������ ����������� PDF'�� �� �� �����.
        foreach ($options as $key => $value) {
            if ($key == "ASCIIHexDecode")
                $_stream = decodeAsciiHex($_stream);
            if ($key == "ASCII85Decode")
                $_stream = decodeAscii85($_stream);
            if ($key == "FlateDecode")
                $_stream = decodeFlate($_stream);
        }
        $data = $_stream;
    }
    // ���������� ��������� ����� ���������.
    return $data;
}
function getDirtyTexts(&$texts, $textContainers) {
    // ����, � ��� ���� ������ ����������� ������, ��������� �� ���� BT � ET.
    // ���� ����� ������, ����� � ��� �����, ������� ������������ ��������������
    // �� ������. ��������� �����, ���������� ����: [...] TJ � Td (...) Tj
    for ($j = 0; $j < count($textContainers); $j++) {
        // ��������� ��������� ������� "�������" ������ � ������ �������
        // ��������� ��������.
        if (preg_match_all("#\[(.*)\]\s*TJ#ismU", $textContainers[$j], $parts))
            $texts = array_merge($texts, @$parts[1]);
        elseif(preg_match_all("#Td\s*(\(.*\))\s*Tj#ismU", $textContainers[$j], $parts))
            $texts = array_merge($texts, @$parts[1]);
    }
}
function getCharTransformations(&$transformations, $stream) {
    // � ���� ���! ����� ��������� � ��� �������, ����� �� ������������ �� PHP, �� �������
    // ���� � �������� �������. ������ �� ������� ������, ����� ������ �� �������
    // ������������� ��������. ��� �������������� � ���� ����� ������� ������ ������� � hex-
    // ������������� � ������, ��� ���� � ��������� ������������������.
    // ��� ���������� ��������� ����, ������� �� ������ �������� � ������� ������.
    // ������ ����� beginbfchar � endbfchar ��������������� ���� hex-��� � ������ (��� 
    // ������������������ �����) �� �����������. ����� beginbfrange � endbfrange ������������
    // �������������� ��� �������������������� ������, ��� ���������  ���������� �����������.
    preg_match_all("#([0-9]+)\s+beginbfchar(.*)endbfchar#ismU", $stream, $chars, PREG_SET_ORDER);
    preg_match_all("#([0-9]+)\s+beginbfrange(.*)endbfrange#ismU", $stream, $ranges, PREG_SET_ORDER);
    // ������� ������������ ��������� �������. ������ �������������� �������� ���:
    // - <0123> <abcd> -> 0123 ����������������� � abcd;
    // - <0123> <abcd6789> -> 0123 ����������������� � ��������� �������� (� ������ ������ abcd � 6789)
    for ($j = 0; $j < count($chars); $j++) {
        // ����� ������� ������, ���� ����� ������������ ���������� �����, ������� �����
        // ���������. �� ����� ����� ��� � �������.
        $count = $chars[$j][1];
        $current = explode("\n", trim($chars[$j][2]));
        // ������ ������ �� ������ �������.
        for ($k = 0; $k < $count && $k < count($current); $k++) {
            // ����� ����� ���������� ����� ��������� �������������. �� ��������, ���
            // ���� �������� ������ ������, �� ������ �������� ����.
            if (preg_match("#<([0-9a-f]{2,4})>\s+<([0-9a-f]{4,512})>#is", trim($current[$k]), $map))
                $transformations[str_pad($map[1], 4, "0")] = $map[2];
        }
    }
    // ������ ��������� � �������������������. �� ������������ ������������������ ������
    // ���� �����, � ������:
    // - <0000> <0020> <0a00> -> � ���� ������ <0000> ����� �������� �� <0a00>, <0001> �� <0a01> �
    //   ��� ����� �� <0020>, ������� ����������� � <0a20>.
    // - <0000> <0002> [<abcd> <01234567> <8900>] -> ��� �� �������� ���� �� �������. �������, �������
    //   ��������� ��������� ����� <0000> � <0002> (������ � 0001 ���). ����� ������� �� ���������
    //   ����������� �������� �� ���������� ������: 0000 -> abcd, 0001 -> 0123 4567, � 0002 -> 8900.
    for ($j = 0; $j < count($ranges); $j++) {
        // ����� ��������� � ����������� ��������� ��� �������������.
        $count = $ranges[$j][1];
        $current = explode("\n", trim($ranges[$j][2]));
        // ���������� �������.
        for ($k = 0; $k < $count && $k < count($current); $k++) {
            // � ������ ������ ������������������ ������� ����.
            if (preg_match("#<([0-9a-f]{4})>\s+<([0-9a-f]{4})>\s+<([0-9a-f]{4})>#is", trim($current[$k]), $map)) {
                // ��������� ������ � 10-���� ������� ���������, ��� ����� ��������� ������.
                $from = hexdec($map[1]);
                $to = hexdec($map[2]);
                $_from = hexdec($map[3]);
                // � ������ ������������� ��������� ��� �������� ����� ������� � ������ ������������������.
                // �� ������������ �� ������ �������� ������� ����, ���� ����� hex-���� ������ ������ ��������.
                for ($m = $from, $n = 0; $m <= $to; $m++, $n++)
                    $transformations[sprintf("%04X", $m)] = sprintf("%04X", $_from + $n);
            // ������ �������.
            } elseif (preg_match("#<([0-9a-f]{4})>\s+<([0-9a-f]{4})>\s+\[(.*)\]#ismU", trim($current[$k]), $map)) {
                // ����� ������ � ����� ������������������. ���� ������ � ���������� �������
                // �� (�����)���������� ��������.
                $from = hexdec($map[1]);
                $to = hexdec($map[2]);
                $parts = preg_split("#\s+#", trim($map[3]));
                
                // ������� ������ � ����������� ��������������� ������ �� ����� ���������.
                for ($m = $from, $n = 0; $m <= $to && $n < count($parts); $m++, $n++)
                    $transformations[sprintf("%04X", $m)] = sprintf("%04X", hexdec($parts[$n]));
            }
        }
    }
}
function getTextUsingTransformations($texts, $transformations) {
    // �������� ������ ���� - ��������� ������ �� "�������" ������.
    // � PDF "�������" ��������� ������ ����� ��������� ��������� �������:
    // - (I love)10(PHP) - � ������ ������ � () ��������� ��������� ������,
    //   � 10 ������ ����� �������� ��������.
    // - <01234567> - � ������ ������ �� ����� ���� � ����� ���������,
    //   � �� HEX-�������������: 0123 � 4567. ��� ������� ������� ���������
    //   �� ������� ��������� � ������� �������������.
    // - (Hello, \123world!) - \123 ����� ������ � 8-���� ������� ���������,
    //   ��� ����� ��������� ����� ����������.
    // ��� � �������. �������� ���������� ����������� ��������� ������,
    // ��������� "�������" ��������� �������"
    $document = "";
    for ($i = 0; $i < count($texts); $i++) {
        // ��� ���������� ��� ��������, ����� ����� ��������� � <> (hex) � �
        // () (plain-�������������.
        $isHex = false;
        $isPlain = false;
        $hex = "";
        $plain = "";
        // ����������� ��������� ������� ��������� �����.
        for ($j = 0; $j < strlen($texts[$i]); $j++) {
            // �������� ������� ������
            $c = $texts[$i][$j];
            // ...� ����������, ��� ��� � ��� ������.
            switch($c) {
                // ����� ���� ���������� 16-���� ������
                case "<":
                    $hex = "";
                    $isHex = true;
                break;
                // Hex-������ �����������, ����� �� ���������.
                case ">":
                    // ���� ������ �� ������� �� 4 �������...
                    $hexs = str_split($hex, 4);
                    // ...� �������, ��� �� ����� � ������ �������� �������
                    for ($k = 0; $k < count($hexs); $k++) {
                        // �������� ��������, ��� � ������� ������ 4 ��������, ������������
                        // ������� ��� ��������� ����� ������ ������.
                        $chex = str_pad($hexs[$k], 4, "0");
                        // ��������� ������� ������� hex-���� � ��������������. � ������
                        // ������, �������� ����� �� ���������.
                        if (isset($transformations[$chex]))
                            $chex = $transformations[$chex];
                        // ����� � �������� ������ ����� Unicode-������.
                        $document .= html_entity_decode("&#x".$chex.";");
                    }
                    // Hex-������ �����������, �� �������� ������� �� ���� ����.
                    $isHex = false;
                break;
                // ������� ����� "�������" ������
                case "(":
                    $plain = "";
                    $isPlain = true;
                break;
                // �� � ��� �������, ���� ����� �����-������ ����������.
                case ")":
                    // ��������� ���������� ����� � �������� �����.
                    $document .= $plain;
                    $isPlain = false;
                break;
                // ������ �������������, ������, ��� ����� �� ���.
                case "\\":
                    $c2 = $texts[$i][$j + 1];
                    // ���� ��� \ ��� ���� �� ������� ������, �� ����� �� �������, ��� ����.
                    if (in_array($c2, array("\\", "(", ")"))) $plain .= $c2;
                    // ��������, ��� ���������� ������ ��� ��� ����� ������� ������, ������������.
                    elseif ($c2 == "n") $plain .= '\n';
                    elseif ($c2 == "r") $plain .= '\r';
                    elseif ($c2 == "t") $plain .= '\t';
                    elseif ($c2 == "b") $plain .= '\b';
                    elseif ($c2 == "f") $plain .= '\f';
                    // ����� ��������, ��� �� \ ��� �����. ����� ���� ����� ���� �� 3, ��� ��������
                    // ����� ������� � 8-���� ������� ���������. ��������� � ��.
                    elseif ($c2 >= '0' && $c2 <= '9') {
                        // ��� ����� ��� ����� �� �����, � ������ �����.
                        $oct = preg_replace("#[^0-9]#", "", substr($texts[$i], $j + 1, 3));
                        // ���������� ������� �������� �� ��������, ����� �������� �������
                        // "�������� �������" ���������.
                        $j += strlen($oct) - 1;
                        // � "������" ����� ����� ��������������� ������.
                        $plain .= html_entity_decode("&#".octdec($oct).";");
                    }
                    // �� �������� ������� "�������� �������" �� ������, ��� �� ����, ������
                    // ����� �� ����.
                    $j++;
                break;
                // ���� �� ����� ���� ���-�� ������, �� ����� ������� ������ ���� ��
                // ��������� hex-������ (���� �� ����� ��� ������ <),
                default:
                    if ($isHex)
                        $hex .= $c;
                    // ���� � "������" ������, ���� ���� ������� ������� ������.
                    if ($isPlain)
                        $plain .= $c;
                break;
            }
        }
        // ����� ������ �������� ���������� �����.
        $document .= "\n";
    }
    // ���������� ���������� ��������� ������.
    return $document;
}
function pdf2text($filename) {
    // ������ ������ �� pdf-����� � ������, ���������, ��� ���� ����� ���������
    // �������� ������.
    $infile = @file_get_contents($filename, FILE_BINARY);
    if (empty($infile))
        return "";
    // ������ ������. ��� ��������� �������� ��� ��������� ������ �� �����.
    // � 1�� ������� �� �������� ���� "�������" ������, � �����������������,
    // � ��������� hex � ��� �����.
    $transformations = array();
    $texts = array();
    // ��� ������ ������� ������ ���� �������� �� pdf-�����.
    preg_match_all("#obj(.*)endobj#ismU", $infile, $objects);
    $objects = @$objects[1];
    // ����� ��������, �� ��� ����� - ������ ������, ��� ����� ���������
    // ����� ����� ����������� � �� ������ "��������", ��������, �� �� ������.
    for ($i = 0; $i < count($objects); $i++) {
        $currentObject = $objects[$i];
        // ���������, ���� �� � ������� ������� ����� ������, ����� ������ ��
        // ���� � ������� gzip.
        if (preg_match("#stream(.*)endstream#ismU", $currentObject, $stream)) {
            $stream = ltrim($stream[1]);
            // ������ ��������� ������� �������, ��� ���������� ������ ���������
            // ������, ������� ������ ����������� ���������, ����� ��������
            // ����������
            $options = getObjectOptions($currentObject);
            if (!(empty($options["Length1"]) && empty($options["Type"]) && empty($options["Subtype"])))
                continue;
            // ����, ����� ���� "��������" �����, �������������� ��� �� ���������
            // �������������. ����� ����� �������� �� ����� ���� ������ � plain text.
            $data = getDecodedStream($stream, $options); 
            if (strlen($data)) {
                // ����, ��� ����� ����� ��������� ������ � ������� ������.
                // � ������ ������ ��������� "�������" ����� ���������� � ���������
                // ��������� �� �����
                if (preg_match_all("#BT(.*)ET#ismU", $data, $textContainers)) {
                    $textContainers = @$textContainers[1];
                    getDirtyTexts($texts, $textContainers);
                // � ��������� ������, �������� ����� ���������� �������������,
                // ������� ����� ������������ �� ������ ����.
                } else
                    getCharTransformations($transformations, $data);
            }
        }
    }
    // �� ��������� ���������� �������� pdf-���������, �������� ������ ����������
    // ��������� ������ � ������ ���������� �������������. �� ���������, ����������
    // ���������� ���������.
    return getTextUsingTransformations($texts, $transformations);
}
?>