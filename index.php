<?php
/*
Arvin2, créé par Olivier FAURAX le 12/8/2013
Dernière version sur https://github.com/ofaurax/arvin2
*/

require_once('config.php');

require_once($arv_config['theme_dir'].'/Itheme.php');
require_once($arv_config['theme_dir'].'/'.$arv_config['theme'].'/theme.php');

require_once('util.php');

$token = (isset($_GET['token']) ? $_GET['token'] : '');
$token_ok = (token_found($arv_config['tokens_dir'], $token) >= 2);
    
$f = 'Archives_HMAP.csv';
?>
<!DOCTYPE html>
<html>
<head>
<title>Arvin2 : archiviste virtuel</title>
<meta charset="utf-8"/>

<?php
$theme->getCSS();
?>

<style type="text/css">

table {
  width: 100%;
}

td {
 border: 1px solid grey;
}

td, th {
 padding : 2px;
}

li {
  display: inline-block;
    margin-right: 2em;
}

.manquant {
    text-decoration: line-through;
} 
.hmap.manquant {
    color: orange;
}
.oblig.manquant {
    color: red;
    font-weight: bold;
}
li {
  color: grey;
}
.hmap {
    color: black;
}

@media print {
    body { font-size:8pt; }
    h1 { font-size:10pt; }
    .noprint { display:none; }
}

</style>
</head>
<body>
<div id="page">
<?php
$theme->getBanner();
?>

<?php
$sv = ''; $tv = ''; $lv = 0;
// recherche
if(isset($_GET['s'])) $sv = htmlspecialchars($_GET['s']);
// token
if(isset($_GET['t'])) $tv = (int)($_GET['t']);
// avec instruments
if(isset($_GET['l'])) $lv = ($_GET['l'] ? true : false);
?>
<!-- <p style="float:right;padding:0;margin:0"><em><?php echo $f ?></em></p> -->
    <h1 class="entry-title">Arvin <span style="color:#ff379b">l'archiviste</span></h1>
<form method="get" class="noprint">
<?php
if($token_ok)
{
  echo '<input type="hidden" name="token" value="'.$token.'" />';
}
?>
<input name="s" value="<?php echo $sv ?>" />
<input type="submit" value="Cherche !"/>
Tri par <select name="t">
<option value="0" <?php if($tv==0) echo 'selected' ?>>Titre</option>
<option value="1" <?php if($tv==1) echo 'selected' ?>>Auteur</option>
<option value="2" <?php if($tv==2) echo 'selected' ?>>Référence</option>
</select>
<!--
     <input type="checkbox" id="l" name="l" <?php if($lv) echo 'checked' ?>/><label for="l">avec instruments</label>
-->
</form>

<?php
$l = 1;
$of = fopen($f, 'r');
fgetcsv($of);
$entete = fgetcsv($of);
while($l)
{
    $l = fgetcsv($of);
    $data[] = $l;
}

// Trie par la colonne $i
function tri_ligne($a, $b, $i)
{
    if($a[$i] == $b[$i]) return 0;
    if($a[$i] < $b[$i] ) return -1;
    return 1;
}

function tri_par_titre($a, $b) {return tri_ligne($a, $b, 0);}
function tri_par_auteur($a, $b) {return tri_ligne($a, $b, 1);}
function tri_par_ref($a, $b) {return tri_ligne($a, $b, 3);}

switch($tv)
{
    default:
    case 0:
    usort($data, 'tri_par_titre');
    break;
    case 1:
    usort($data, 'tri_par_auteur');
    break;
    case 2:
    usort($data, 'tri_par_ref');
    break;
}

$pgm = array();
if(substr($sv, 0, 4) == 'pgm:'
   && is_file($arv_config['pgm_dir'].'/'.substr($sv, 4).'.txt'))
{
    echo '<h2 class="entry-title">Programme '.str_replace('_', ' ', substr($sv, 4)).'</h2>';
    $pgm = file(
        $arv_config['pgm_dir'].'/'.substr($sv, 4).'.txt',
        FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
}

echo '<table>';
echo '<tr>';
$i = 0;
foreach($entete as $c)
{
    echo '<th>'.ucfirst(strtolower($c)).'</th>';
    $i++;
    //if(!$lv)
    {
        if($i>=4) break;
    }
}

if($token_ok)
{
    echo '<th>Téléchargement</th>';
}
else
{
    echo '<th></th>';
}
echo '</tr>';

foreach($data as $l)
{
    if(!$l || !$l[0]) continue;

    if($sv)
    {
        if(
            stripos($l[0], $sv) === FALSE
            && stripos($l[1], $sv) === FALSE
            && stripos($l[2], $sv) === FALSE
            && stripos($l[3], $sv) === FALSE
            && !in_array($l[3], $pgm)
        )
        continue;
    }

    echo '<tr>';
    for($i = 0; $i < sizeof($l); $i++)
    {
        $c = $l[$i];
        echo '<td>';
        echo $c;
        if($i==0)
        {
            echo ' [<a href="http://www.youtube.com/results?search_query='.urlencode($c.', '.$l[$i+1].', '.$l[$i+2]).'">youtube</a>]';
            echo ' [<a href="http://musicainfo.net/quiksrch.php?vol='.urlencode($c).'">musicainfo</a>]';
        }
        echo '</td>';
        //if(!$lv)
        {
            if($i>=3) break;
        }
    }

    $oblig_compt = 0;
    $hmap_compt = 0;
    for($j=4; $j<count($l);$j++)
    {
        if(!$entete[$j]) continue; // passe les colonnes vides
        
        $en_stock = ($l[$j] !== '' && $l[$j] !== '0');
        if($en_stock && in_array($entete[$j], $instru_oblig)) $oblig_compt++;
        if($en_stock && in_array($entete[$j], $instru_hmap)) $hmap_compt++;
    }
    if($token_ok && is_dir($arv_config['docs_dir'].'/'.$l[3]))
    {
        echo '<td><a href="list.php?ref='.$l[3].'&token='.$_GET['token'].'">Documents</a></td>';
    }
    else
    {
        echo '<td></td>';
    }
    echo '</tr>';

    if($lv)
    {
        echo '</table>';
        echo '<ul>';
        $oblig_compt = 0;
        for($j=4; $j<count($l);$j++)
        {
            if(!$entete[$j]) continue; // passe les colonnes vides

            $class = '';
            if(in_array($entete[$j], $instru_hmap)) $class .= ' hmap';
            if(in_array($entete[$j], $instru_oblig)) $class .= ' oblig';

            $en_stock = ($l[$j] !== '' && $l[$j] !== '0');
            if(!$en_stock) $class .= ' manquant';
            if($en_stock && in_array($entete[$j], $instru_oblig)) $oblig_compt++;

            echo '<li';
            if($class) echo ' class="'.$class.'"';
            echo '>';
            echo $entete[$j];
            if($en_stock) echo ' ('.$l[$j].')';
            echo '</li>';
        }
        echo '</ul>';

        echo '<table>';
    }

}
echo '</table>';

// Listing programmes
$tmpd = opendir($arv_config['pgm_dir']);

echo '<h3>Programmes</h3>';
$sep = '';
$programmes = [];

while($d = readdir($tmpd))
{
    if($d[0] == '.') continue;
    $r = preg_match("/(\d+)/", $d, $m);
    if($r)
    {
        $k = $m[0];
    }
    else
        $k = "Autre";
    $programmes[$k][] = $d;
}
krsort($programmes);

foreach($programmes as $k => $p)
{
    echo $k." : ";
    $sep = '';
    foreach($p as $d)
    {
        if(substr($d, -4) != '.txt') continue;
        $p = substr($d, 0, -4);
        echo $sep.'<a href="?token='.$token.'&amp;s=pgm:'.$p.'">'.
             $p.'</a>'."\n";
        $sep = ' | ';
    }
    echo '<br/>';
}


?>
</body>
</html>
