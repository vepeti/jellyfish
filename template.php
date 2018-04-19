<?php

class template
{
protected $file;
protected $values = array();

public function __construct($file)
{
    $this->file = $file;
}

public function set($key, $value)
{
    $this->values[$key] = $value;
}

public function output()
{
if (!file_exists($this->file)) 
{
    return "Error loading template file ($this->file).";
}
$output = file_get_contents($this->file);

// LOOP IN ARRAY
$output=$this->loop($output);

// CONDITION
$output=$this->condition($output);

// FILTERS
$output=$this->filters($output);

return $output;
}

private function condition($string)
{
$string=preg_replace_callback("/\{\{ ?if (\(.+\)) ?}}(.+)\{\{ ?endif ?}}/sU", function ($found)
{
//    $condstring=$this->main_subst($found[1]);
    $condstring=$this->filters($found[1]);
    $cond=eval("return $condstring;");
    if ($cond)
    {
	return $found[2];
    }
    else
    {
	return NULL;
    }
}, $string);
return $string;
}

private function loop($string)
{
$string=preg_replace_callback("/\{\{ ?for ((\w+)=>)?(\w+) in \[@(.+)] ?}}(.+)\{\{ ?endfor ?}}/sU", function($found)
{
    $row=NULL;
    foreach($this->values[$found[4]] as $key=>$element)
    {
	if (preg_match("/\[@".$found[3]."](\[.+])/", $found[5]))
	{
	    $index=preg_replace_callback("/^.*\[@".$found[3]."](\[.+]).*$/sU", function($dim){return $dim[1];}, $found[5]);
	    $str='return $element'.$index.';';
	    $var=eval($str);
	    $row.=preg_replace("/\[@".$found[3]."](\[.+])/U", $var, $found[5]);
	}
	else
	{
	    $string=preg_replace("/\[@".$found[3]."]/", $element, $found[5]);
	    $string=preg_replace("/\[@".$found[2]."]/", $key, $string);
	    $row.=$string;
	}
    }
$row=$this->condition($row);
return $row;
}, $string);
return $string;
}

private function filters($string)
{
// DEFAULT STRING SUBSTITUTION
$string=preg_replace_callback("/\{\{ ?(.+?) ?\| ?default\((.*)\) ?}}/U", function($found)
{
    if (isset($this->values[$found[1]]))
    {
	return $this->values[$found[1]];
    }
    else
    {
	return $found[2];
    }
}, $string);

// ARRAY FUNCTIONS
$string=preg_replace_callback("/\{\{ ?\[@(.+?)] ?\| ?(
|count|rand|first|last|min|max|join\((.*)\)|
) ?}}/U", function($found)
{
    if (is_array($this->values[$found[1]]))
    {
	switch ($found[2])
	{
	    case "count":
		return count($this->values[$found[1]]);
		break;
	    case "rand":
		return array_rand($this->values[$found[1]]);
		break;
	    case "first":
		return reset($this->values[$found[1]]);
		break;
	    case "last":
		return end($this->values[$found[1]]);
		break;
	    case "min":
		return min($this->values[$found[1]]);
		break;
	    case "max":
		return end($this->values[$found[1]]);
		break;
	    case "join($found[3])":
		return implode($found[3], $this->values[$found[1]]);
		break;
	}
    }
    else
    {
	return "Not an Array";
    }
}, $string);

// VARIABLE AND ARRAY SUBSTITUTIONS
$string=$this->main_subst($string);

// STRING FUNCTIONS
if (preg_match("/\{\{.+?}}/", $string))
{
    $string=preg_replace_callback("/\{\{(.+)\|uc}}/U", function($found)
    {return strtoupper($found[1]);}, $string);

    $string=preg_replace_callback("/\{\{ ?(.+) ?\| ?ucf ?}}/U", function($found)
    {return ucfirst($found[1]);}, $string);

    $string=preg_replace_callback("/\{\{ ?(.+?) ?\| ?lcf ?}}/", function($found)
    {return lcfirst($found[1]);}, $string);

    $string=preg_replace_callback("/\{\{ ?(.+?) ?\| ?lc ?}}/", function($found)
    {return strtolower($found[1]);}, $string);

    // MATH FUNCTIONS
    $string=preg_replace_callback("/\{\{ ?(.+?) ?\| ?(
    |sin|cos|tan|asin|acos|atan|log|ceil|floor|round|abs|sqrt|log10|dechex|hexdec|decoct|octdec|bindec|decbin|shuffle|
    ) ?}}/", function($found)
    {return @eval("return $found[2]($found[1]);") ? @eval("return $found[2]($found[1]);") : "$found[1] is Not valid Number";}, $string);

    // HASH FUNCTIONS
    $string=preg_replace_callback("/\{\{ ?(.+?) ?\| ?(
    |md5|sha1|sha256|sha384|sha512|crc32|
    ) ?}}/", function($found)
    {return @eval("return hash($found[2],$found[1]);") ? @eval("return hash($found[2],$found[1]);") : "$found[1] is Not valid Number";}, $string);
}
return $string;
}

private function main_subst($string)
{
$string=preg_replace_callback("/\[@(.+)]((\[.+]){2,})/", function($found)
{return eval('return $this->values[$found[1]]'."$found[2];");}, $string);

$string=preg_replace_callback("/\[@(.+)]\[(.+)]/U", function($found)
{return $this->values[$found[1]][$found[2]];}, $string);
    
$string=preg_replace_callback("/\[@(.+)]/U", function($found)
{return $this->values[$found[1]];}, $string);
return $string;
}

}
?>
