<?php

class misc
{
    public static $test = 0;
    static function get_audio_tags($filename)
    {

        setlocale(LC_ALL, "en_US.UTF-8");
        $fn_quote = escapeshellarg($filename);
        $fetch_cmd = config::getSetting('getters', 'mediainfo') . "  --Inform=\"General;%Duration%\\n%Title%\\n%Track/Position%\\n%Album%\\n%Performer%\\n%Genre%\\n%Album/Performer%\\n%Recorded_Date%\" " . $fn_quote;
        exec($fetch_cmd, $tag_data, $exit);

        $tag_list = array('DURATION', 'TITLE', 'TRACKNUMBER', 'ALBUM', 'PERFORMER', 'GENRE', 'ALBUM_PERFORMER', 'RECORDED_DATE');

        if (count($tag_data) != count($tag_list))
            return null;

        if(array_search(application::getLanguage(), array('uk', 'ru')) > -1)
        {
            foreach($tag_data as &$tag)
            {
                $tag = misc::cp1252dec($tag);
            }
        }
        
        $tag_array = array_combine($tag_list, $tag_data);

        return $tag_array;
    }

    static function findMp3Header($data, $starting = 0)
    {
        if (strlen($data) < 4)
        {
            return false;
        }

        for ($n = $starting; $n < strlen($data) - 3; $n ++ )
        {
            if (self::readMp3Header(substr($data, $n, 4)) !== false)
            {
                return $n;
            }
        }
        return false;
    }

    
    static function readMp3Header($header)
    {

        if (strlen($header) < 4)
        {
            return false;
        }

        // Convert header string to bits
        $header_bits = unpack("N", $header);

        // Check bits correctness
        if (($header_bits[1] & 0xFFE << 20) != 0xFFE << 20)
        {
            return false;
        }

        // Seems to be ok. Trying to decode
        $mp3_header = array();

        $version_array = array('MPEG Version 2.5 (not an official standard)',
            null, 'MPEG Version 2', 'MPEG Version 1');

        $header_array = array('Unknown', 'Layer III', 'Layer II', 'Layer I');

        $bitrate_array = array(null, 32, 40, 48, 56, 64, 80, 96, 112,
            128, 160, 192, 224, 256, 320, null);

        $sampling_array = array(44100, 48000, 32000, "Unknown");
        $channels_array = array("Stereo", "Joint Stereo", "Dual", "Mono");
        $emphasis_array = array("None", "50/15", null, "CCIT J.17");

        if (($header_bits[1] & 0xF << 12) >> 12 == 0xF)
        {
            return false;
        }

        $mp3_header['version'] = $version_array[($header_bits[1] & 0x3 << 19) >> 19];
        $mp3_header['layer'] = $header_array[($header_bits[1] & 0x3 << 17) >> 17];
        $mp3_header['crc'] = (($header_bits[1] & 0x1 << 15) >> 15) ? "No" : "True";
        $mp3_header['bitrate'] = $bitrate_array[($header_bits[1] & 0xF << 12) >> 12];
        $mp3_header['samplerate'] = $sampling_array[($header_bits[1] & 0x3 << 10) >> 10];
        $mp3_header['padding'] = (($header_bits[1] & 0x1 << 9) >> 9) ? "Yes" : "No";
        $mp3_header['channels'] = $channels_array[($header_bits[1] & 0x3 << 6) >> 6];
        $mp3_header['emphasis'] = $emphasis_array[$header_bits[1] & 0x3];

        // Skip frame if not Layer III
        if ($mp3_header['layer'] != "Layer III")
        {
            misc::writeDebug("Wrong layer: " . $mp3_header['layer'], 1);
            return false;
        }

        //  Skip if wrong sampling rate
        if ($mp3_header['samplerate'] != 44100)
        {
            return false;
        }

        $mp3_header['framesize'] = floor(144000 * $mp3_header['bitrate'] / $mp3_header['samplerate']);

        if ($mp3_header['framesize'] == 0)
        {
            return false;
        }

        $mp3_header['padding'] == "Yes" ? $mp3_header['framesize'] ++ : null;

        return $mp3_header;
    }

    static function searchQueryFilter($text)
    {
        $query = "";
        $words = preg_split("/(*UTF8)((?![\p{L}|\p{N}])|(\s))+/", $text);
        
        
        foreach($words as $word)
        {
            if(strlen($word) > 0)
            {
                //$word = trim($word);
                $query .= "+{$word} ";
            }
        }
        $query .= "*";
        return $query;
    }
    
    static function generateId()
    {
        $id_length = 8;
        $id_chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
        $id = "";
        for ($i = 0; $i < $id_length; $i ++ )
        {
            $id .= substr($id_chars, rand(0, strlen($id_chars) - 1), 1);
        }
        return $id;
    }

    static function convertuSecondsToTime($useconds)
    {
        $seconds = $useconds / 1000;
        return sprintf("%01d:%02d:%02d", floor($seconds / 3600), floor($seconds / 60) % 60, $seconds % 60);
    }

    static function trackDuration($useconds)
    {
        $seconds = floor($useconds / 1000);
        return sprintf("%02d:%02d", floor($seconds / 60), $seconds % 60);
    }

    static function writeDebug($message, $code = 0)
    {
        $file = fopen("/tmp/myownradio.dev.log", "a");
        switch ($code)
        {
            case 0:
                $tag_l = "";
                $tag_r = "";
                break;
            case 1:
                $tag_l = "<red>";
                $tag_r = "</red>";
                break;
            case 2:
                $tag_l = "<grey>";
                $tag_r = "</grey>";
                break;
            case 3:
                $tag_l = "<green>";
                $tag_r = "</green>";
                break;
            default:
                $tag_l = "";
                $tag_r = "";
                break;
        }
        fwrite($file, sprintf("%s %s {$tag_l}%s{$tag_r}\n", date("Y.m.d H:i:s", time()), application::getClient(), htmlspecialchars($message)));
        fclose($file);
    }

    static function mySort($array)
    {
        if ( ! is_array($array))
        {
            return false;
        }
        if (count($array) <= 1)
        {
            return $array;
        }
        $f = 0;
        while (true)
        {
            if ($array[$f] <= $array[$f + 1])
            {
                ++ $f;
                if ($f >= count($array) - 1)
                {
                    return $array;
                }
            }
            else
            {
                $temp = $array[$f + 1];
                $array[$f + 1] = $array[$f];
                $array[$f] = $temp;
                if ($f > 0)
                {
                    -- $f;
                }
            }
        }
    }

    static function outputJSON($code, $data = array())
    {
        return json_encode(array(
            'code' => $code,
            'data' => $data
        ));
    }
    
    static function errorJSON($code)
    {
        exit(json_encode(array(
            'error' => $code
        )));
    }
    
    static function cp1252dec($chars)
    {
        $test = @iconv("UTF-8", "CP1252", $chars);
        if($test)
        {
            return iconv("CP1251", "UTF-8", $test);
        }
        else
        {
            return $chars;
        }
    }
    
    static function execute($data, $_MODULE = NULL)
    {
        ob_start();
        eval("?>" . $data);
        return ob_get_clean();
    }

    static function executeFile($filename, $_MODULE = NULL)
    {
        ob_start();
        include $filename;
        return ob_get_clean();
    }
}
