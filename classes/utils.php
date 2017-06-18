<?php

class Utils{
    /**
     * @return string
     * returns '<br>' if we're outputting to a web browser
     * otherwise returns "\n"
     */
    public static function newLine(){
        if(PHP_SAPI === 'cli')
            return "\n";
        else
            return "<br>";
    }

    public static function tab(){
        if(PHP_SAPI === 'cli')
            return "\t";
        else
            return "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
    }

}
