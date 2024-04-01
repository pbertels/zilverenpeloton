<?php

namespace ZilverenPeloton;

use PhpWebsite\Database;

class ZPController
{
    public function __construct()
    {
    }

    public static function dynamicMenu($d)
    {
        $menu = array();
        $menu[] = array('link' => "./{$d['code']}", 'title' => "Virtueel fietsen");
        if ($d['steun'] != '') {
            $menu[] = array('link' => $d['steun'], 'title' => 'Doe een gift');
        }
        return $menu;
    }

    private static function sortVideos($a, $b)
    {
        if ($a == $b) {
            return 0;
        }
        $aa = stripos($a, 'limb') === false ? 0 : 1;
        $bb = stripos($b, 'limb') === false ? 0 : 1;
        if ($aa == $bb) return 0;
        return ($aa > $bb) ? -1 : 1;
    }

    public static function getVideos($domain, $show = 'all')
    {
        $videos = array();
        $videoMap = array();
        $files = glob('./video/*--*.mp4');
        usort($files, '\ZilverenPeloton\ZPController::sortVideos');
        // echo '<pre>' . print_r($files, true) . '</pre>'; exit;
        foreach ($files as $file) {
            $v = array();
            $info = pathinfo($file);
            $s = explode('--', $info['filename']);
            $v['code'] = $s[0];
            $v['naam'] = str_replace('-', ' ', $s[1]);
            $v['url'] = $domain . $file;
            if (count($s) == 2 || ($show == 'all' || $show == $s[2])) {
                $videos[] = $v;
                $videoMap[$v['code']] = $v;
            }
        }
        return array($videos, $videoMap);
    }

    public static function unknown($pass = false)
    {
        $yaml = "---\n" .
            "title: Het Zilveren Peloton\n" .
            "colour: light\n" .
            "---\n\n";
        if ($pass) {
            $yaml .= "GEEN TOEGANG. Controleer de URL of [keer terug naar de homepage](./).\n\n";
        } else {
            $yaml .= "Deze pagina bestaat niet. Controleer de URL of [selecteer het deelnemende team op de homepage](./).\n\n";
        }
        return array(
            'type' => 'html',
            'stats' => 'unknown',
            'yaml+md' => $yaml,
        );
    }

    public static function getStatus($code, $pass = false)
    {
        $rst = array(
            'doel' => 1000,
            'realisatie' => 0,
            'max' => 1000,
            'acties' => array(),
            'deelnemer' => array(),
        );

        $database = Database::getInstance();
        $stmt = $database->prepare('
            SELECT q.*, a.*
            FROM (
            SELECT d.*, max(v.id) as vid, v.video
            FROM zlvrnpltn_deelnemers d 
            LEFT JOIN zlvrnpltn_video v ON v.deelnemer =  d.id
            WHERE d.code = :code ) q 
            LEFT JOIN (
                SELECT deelnemer, id as aid, type, omschrijving, km, foto, NOW() - updatedate AS recency
                FROM zlvrnpltn_actie 
                WHERE YEAR(updatedate) = YEAR(NOW()) 
                ORDER BY type DESC, km DESC
            ) a ON a.deelnemer = q.id;
        ');
        $stmt->bindParam(':code', $code, \PDO::PARAM_STR);
        if ($stmt->execute() && $stmt->setFetchMode(\PDO::FETCH_ASSOC) == 1) {
            $rst['acties'] = $stmt->fetchAll();
        }
        if (count($rst['acties']) != 0) {
            $rst['deelnemer'] = $rst['acties'][0];
            unset($rst['deelnemer']['aid']);
            unset($rst['deelnemer']['type']);
            unset($rst['deelnemer']['omschrijving']);
            unset($rst['deelnemer']['km']);
            unset($rst['deelnemer']['foto']);
            if (!$pass) unset($rst['deelnemer']['pass']);
        }

        $rst['realisatie'] = 0;
        $newacties = array();
        foreach ($rst['acties'] as $actie) {
            unset($actie['id']);
            unset($actie['code']);
            unset($actie['organisatie']);
            unset($actie['straat']);
            unset($actie['nummer']);
            unset($actie['postcode']);
            unset($actie['gemeente']);
            unset($actie['email']);
            unset($actie['steun']);
            unset($actie['doel']);
            unset($actie['playlist']);
            unset($actie['vid']);
            unset($actie['video']);
            unset($actie['foto']);
            unset($actie['pass']);
            $rst['realisatie'] += $actie['km'];
            $newacties[$actie['aid']] = $actie;
        }
        $rst['acties'] = $newacties;
        $max = $rst['realisatie'] < $rst['doel'] ? $rst['doel'] : 50 * round(pow(10, ceil(log10($rst['realisatie']) / 0.1) * 0.1) / 50);
        $rst['max'] = $max < 3000 ? $max : 1000 * ceil($max / 1000);

        return $rst;
    }

    public function all($params)
    {
        $yaml = "---\n" .
            "title: DE <span class=\"big\">1000</span> KM\n" .
            "subtitle: Het Zilveren Peloton\n" .
            "subsubtitle: Samen trappen tegen Kanker\n" .
            "colour: white\n" .
            "---\n\n";

        if (!$params->cijfers) {
            $yaml .= "### Wat? \n\n";
            $yaml .= "{.intro} Bewoners uit verschillende woonzorgcentra zetten drie dagen lang, van 15 tot en met 17 mei, de voeten op de trappers. Samen proberen ze per woonzorgcentrum 1000 km op de teller te krijgen. En dat ten voordele van kankeronderzoek. \n\n";
            $yaml .= "### Waarom? \n\n";
            $yaml .= "{.intro} &lsquo;Je hebt kanker.&rsquo; Het verdict valt elk jaar voor meer dan 40.000 Vlamingen. \n\n";
            $yaml .= "Hoewel kanker steeds beter kan worden behandeld, overleeft &eacute;&eacute;n op drie de ziekte niet. Wie kanker wel overleeft, ondervindt vaak nog langdurige gevolgen. Kankeronderzoek blijft dus broodnodig. \n\n";
            $yaml .= "Daarom springen het zilveren peloton en Kom op tegen Kanker bij. De giften die het zilveren peloton verzamelt, worden door Kom op tegen Kanker ge&iuml;nvesteerd in onderzoek om kankerpati&euml;nten hogere overlevingskansen en een betere levenskwaliteit te bieden.  \n\n";
            $yaml .= "Zo maken de deelnemers van het zilveren peloton dus voor heel wat kankerpati&euml;nten het verschil. \n\n";
            $yaml .= " \n\n";
        }

        if ($params->cijfers) {
            $yaml .= "---------------\n{.light}\n\n";
            $yaml .= "### Onderstaande teams doen mee: \n";
        }

        $database = Database::getInstance();
        $stmt = $database->prepare('
            SELECT d.*, ROUND(10 * SUM(a.km)) / 10 AS totaal, COUNT(a.id) AS aantal
            FROM zlvrnpltn_deelnemers d
            INNER JOIN zlvrnpltn_actie a ON d.id=a.deelnemer
            GROUP BY d.id
            ORDER BY (CASE WHEN d.id = 1 THEN 0 ELSE 1 END), d.organisatie;
        ');
        if ($stmt->execute() && $stmt->setFetchMode(\PDO::FETCH_ASSOC) == 1) {
            $deelnemers = $stmt->fetchAll();
        }
        if ($params->cijfers) {
            foreach ($deelnemers as $d) {
                $yaml .= "- {$d['organisatie']}: {$d['totaal']} kilometers uit {$d['aantal']} acties - [publieke link](./{$d['code']}) - [geheime link](./{$d['code']}/admin/{$d['pass']}) \n";
            }
        }

        return array(
            'type' => 'html',
            'stats' => 'all',
            'yaml+md' => $yaml,
        );
    }

    public function delete($params)
    {
        $list = ZPController::getStatus($params->code);
        if (!$list) return ZPController::unknown();
        $d = $list['deelnemer'];

        $yaml = "---\n" .
            "title: {$d['organisatie']} uit {$d['gemeente']}\n" .
            "colour: white\n" .
            "---\n\n";
        $yaml .= "---------------\n{.light}\n\n";

        if (isset($_GET['aid']) && is_numeric($_GET['aid']) && isset($list['acties'][$_GET['aid']])) {
            $default = $list['acties'][$_GET['aid']];
            $default['deelnemer'] = $d['id'];
            $default['verwijderen'] = '';
            $titel = "# Verwijderen '{$default['omschrijving']}': ben je zeker?";
        } else return ZPController::unknown();
        $formProperties = array(
            'aid' => array('type' => 'hidden'),
            'deelnemer' => array('type' => 'hidden'),
            'verwijderen' => array(
                'label' => 'Typ hieronder het woord "verwijderen" als je deze actie wil verwijderen',
                'description' => '',
                'type' => 'text',
                'required' => true,
                'validation' => 'verwijderen'
            ),
            'submit' => 'Verwijderen',
        );

        list($valid, $actie, $html) = ZPController::processForm($_POST, $default, $formProperties);
        if (isset($actie['verwijderen']) && $actie['verwijderen'] == 'verwijderen') {
            $database = Database::getInstance();
            $yaml .= "# Actie verwijderd \n\n";
            $SQL = "DELETE FROM zlvrnpltn_actie WHERE id={$actie['aid']} ";
            $stmt = $database->prepare($SQL);
            $stmt->execute();
            $caching = date('U');
            $yaml .= "[Keer terug naar het overzicht](./{$d['code']}/admin/{$params->pass}?caching={$caching})\n";
        } else {
            $yaml .= $titel;
            $yaml .= $html;
        }

        return array(
            'type' => 'html',
            'stats' => 'overview',
            'yaml+md' => $yaml,
            'menu' => ZPController::dynamicMenu($d),
        );
    }

    public function input($params)
    {
        $list = ZPController::getStatus($params->code);
        if (!$list) return ZPController::unknown();
        $d = $list['deelnemer'];

        $yaml = "---\n" .
            "title: {$d['organisatie']} uit {$d['gemeente']}\n" .
            "colour: white\n" .
            "---\n\n";
        $yaml .= "---------------\n{.light}\n\n";

        $default = array(
            'aid' => -1,
            'deelnemer' => $d['id'],
            'type' => 'individueel',
            'omschrijving' => '',
            'km' => 0,
            'foto' => '',
        );
        $formProperties = array(
            'aid' => array('type' => 'hidden'),
            'deelnemer' => array('type' => 'hidden'),
            'type' => array(
                'label' => 'Welk soort actie wil je ingeven?',
                'type' => 'select',
                'options' => array('individueel', 'collectief'),
                'required' => true
            ),
            'omschrijving' => array(
                'label' => 'Korte omschrijving voor de actie',
                'description' => 'Bijv. naam van de persoon of naam van het evenement bij een collectieve actie',
                'type' => 'text',
                'required' => true
            ),
            'km' => array(
                'label' => 'Hoeveel kilometers zijn er gereden?',
                'type' => 'number',
                'required' => true
            ),
            'submit' => 'Actie toevoegen',
        );
        if (isset($_GET['aid']) && is_numeric($_GET['aid']) && isset($list['acties'][$_GET['aid']])) {
            $default = $list['acties'][$_GET['aid']];
            $default['deelnemer'] = $d['id'];
            $titel = "# Aanpassen actie '{$default['omschrijving']}'";
        } else {
            $titel = '# Nieuwe actie toevoegen ';
        }

        list($valid, $actie, $html) = ZPController::processForm($_POST, $default, $formProperties);
        if ($valid) {
            $database = Database::getInstance();
            if ($actie['aid'] == -1) {
                $yaml .= "# Actie toegevoegd \n\n";
                $SQL = 'INSERT IGNORE INTO zlvrnpltn_actie (deelnemer, type, omschrijving, km)
                    VALUES (:deelnemer, :type, :omschrijving, :km) ';
            } else {
                $yaml .= "# Actie gewijzigd \n\n";
                $SQL = "UPDATE zlvrnpltn_actie SET deelnemer=:deelnemer, type=:type, omschrijving=:omschrijving, km=:km, updatedate=NOW()
                    WHERE id={$actie['aid']} ";
            }
            $stmt = $database->prepare($SQL);
            $stmt->bindParam(':deelnemer', $actie['deelnemer'], \PDO::PARAM_INT);
            $stmt->bindParam(':type', $actie['type'], \PDO::PARAM_STR);
            $stmt->bindParam(':omschrijving', $actie['omschrijving'], \PDO::PARAM_STR);
            $stmt->bindParam(':km', $actie['km'], \PDO::PARAM_STR);
            $stmt->execute();
            $caching = date('U');
            $yaml .= "[Keer terug naar het overzicht](./{$d['code']}/admin/{$params->pass}?caching={$caching})\n";
        } else {
            $yaml .= $titel;
            $yaml .= $html;
        }

        return array(
            'type' => 'html',
            'stats' => 'overview',
            'yaml+md' => $yaml,
            'menu' => ZPController::dynamicMenu($d),
        );
    }

    public static function processForm($post, $default, $formProperties)
    {
        $valid = false;
        $data = array();
        $html = '';
        $HASH = '__';

        $form = array();
        // form opbouwen
        foreach ($formProperties as $field => $param) {
            $info = array(
                'type' => $field == 'submit' ? 'submit' : 'text',
                'value' => htmlentities(
                    isset($post[$field . $HASH]) ? $post[$field . $HASH] : (isset($get[$field]) ? $get[$field] : (isset($default[$field]) ? $default[$field] : ''))
                ),
                'label' => '',
                'options' => array(),
                'required' => false,
                'error' => false,
                'description' => '',
                'validation' => '',
                'class' => '',
            );
            if (is_array($param)) {
                foreach ($param as $key => $value) {
                    if (array_key_exists($key, $info)) {
                        $info[$key] = $value;
                    }
                }
            } else {
                $info['label'] = trim('' . htmlentities($param));
            }
            if ($info['type'] == 'submit') {
                $info['value'] = $info['label'];
                $info['class'] .= ' btn';
            } else if ($info['type'] == 'textarea') {
                $info['rows'] = 5;
                $info['cols'] = 25;
            }
            $form[$field] = $info;
        }
        // valideren
        $correct = 0;
        foreach ($form as $field => $info) {

            $error = false;
            if ($info['type'] == 'email' && $info['value'] != '') {
                if (!filter_var($info['value'], FILTER_VALIDATE_EMAIL)) {
                    $error = true;
                }
            }
            if ($info['type'] == 'number' && $info['value'] != '') {
                if (!is_numeric($info['value'])) {
                    $alternative = str_replace(',', '.', $info['value']);
                    if (is_numeric($alternative)) {
                        $form[$field]['value'] = $alternative;
                    } else {
                        $error = true;
                    }
                } else if ($info['required'] && $info['value'] == 0) {
                    $error = true;
                }
            }
            if ($info['required'] && isset($post[$field . $HASH]) && trim($info['value']) == '') {
                $error = true;
            }
            if ($info['required'] && isset($post[$field . $HASH]) && isset($info['validation']) && $info['validation'] != '' && trim($info['value']) != $info['validation']) {
                $error = true;
            }
            if ($info['required'] && $info['type'] == 'checkbox' && $info['value'] != 1) {
                $error = true;
            }
            $form[$field]['error'] = $error;
            if (!$error) {
                $correct++;
            }
        }
        if (isset($post['submit' . $HASH]) && $correct == count($form)) {
            $valid = true;
            foreach ($default as $key => $value) {
                $data[$key] = isset($form[$key]) ? $form[$key]['value'] : $value;
            }
            $html = '';
        } else {
            // create html
            $html = '';
            $html .= '<form method="post">' . "\n";
            foreach ($form as $field => $info) {
                if ($info['error']) $info['class'] .= ' error';
                $html .= '<div>';
                $label = '<label class="' . $info['type'] . '" for="' . $field . $HASH . '">' . $info['label'];
                if ($info['required']) $label .= ' <span class="required">*</span>';
                $label .= '</label>';
                if ($info['type'] == 'textarea') {
                    $element = '<textarea ' . (trim($info['class']) != '' ? 'class="' . trim($info['class']) . '" ' : '') . 'id="' . $field . $HASH . '" name="' . $field . $HASH . '" rows="' . $info['rows'] . '" cols="' . $info['cols'] . '">' . $info['value'] . '</textarea>';
                } else {
                    $html_type = $info['type'];
                    $extra = '';
                    if ($info['type'] == 'number') {
                        $html_type = 'text';
                    }
                    $element = '<input type="' . $html_type . '" ' . (trim($info['class']) != '' ? 'class="' . trim($info['class']) . '" ' : '') . 'id="' . $field . $HASH . '"' . $extra . ' name="' . $field . $HASH . '" value="' . $info['value'] . '" />';
                }

                if ($info['type'] == 'submit') {
                    $html .= $element;
                } else if ($info['type'] == 'radio') {
                    $html .= '<p>' . $info['label'] . '</p>';
                    foreach ($info['options'] as $option) {
                        $html .= '<input type="radio" id="' . $option . '" name="' . $field . $HASH . '" value="' . $option . '"' . ($info['value'] == $option ? ' checked' : '') . '>';
                        $html .= '<label for="' . $option . '">' . $option . '</label>';
                    }
                } else if ($info['type'] == 'select') {
                    $html .= $label;
                    $html .= '<select id="' . $field . $HASH . '" name="' . $field . $HASH . '">' . "\n";
                    foreach ($info['options'] as $option) {
                        $html .= '<option value="' . $option . '"' . ($info['value'] == $option ? ' selected' : '') . '>' . $option . '</option>' . "\n";
                    }
                    $html .= '</select>' . "\n";
                } else if ($info['type'] == 'checkbox') {
                    $html .= $element . ' ' . $label;
                } else {
                    $html .= $label . ' ' . $element;
                }
                $html .= '</div>';
                $html .= "\n";
            }
            $html .= '</form>' . "\n";
        }
        return array($valid, $data, $html);
    }

    public function changeVideo($params)
    {
        $list = ZPController::getStatus($params->code, true);
        if (!$list) return ZPController::unknown();
        $d = $list['deelnemer'];
        if ($d['pass'] != $params->pass) return ZPController::unknown(true);

        $database = Database::getInstance();
        $SQL = 'INSERT IGNORE INTO zlvrnpltn_video (deelnemer, video)
                VALUES (:deelnemer, :video) ';
        $stmt = $database->prepare($SQL);
        $stmt->bindParam(':deelnemer', $d['id'], \PDO::PARAM_INT);
        $stmt->bindParam(':video', $params->video, \PDO::PARAM_STR);
        $stmt->execute();
        $params->mode = 'admin';
        return $this->overview($params);
    }

    public function overview($params)
    {
        $list = ZPController::getStatus($params->code, true);
        if (!$list) return ZPController::unknown();
        $d = $list['deelnemer'];

        if ($d['pass'] != $params->pass && $params->pass != '4ll4reas') return ZPController::unknown(true);

        $yaml = "---\n" .
            "title: {$d['organisatie']} uit {$d['gemeente']}" .
            "\nsubtitle: Administratie\n" .
            "colour: white\n" .
            "---\n\n";

        $yaml .= "---------------\n{.light}\n\n";
        if ($params->mode == 'admin') {
            $yaml .= "# Kilometers \n\n";
        }
        if ($list['realisatie'] == 0) {
            $yaml .= "Help {$d['organisatie']} om aan het doel van {$list['doel']} kilometers te komen!\n\n";
        } else {
            $yaml .= "{$d['organisatie']} verzamelde al heel wat kilometers dankzij deze acties: \n";
            foreach ($list['acties'] as $actie) {
                if ($params->mode == 'admin') {
                    $edit = " [[aanpassen]](./{$d['code']}/input/{$params->pass}?aid={$actie['aid']})";
                    $edit .= " [[verwijderen]](./{$d['code']}/delete/{$params->pass}?aid={$actie['aid']})";
                } else {
                    $edit = '';
                }
                if ($actie['type'] == 'individueel') {
                    $yaml .= "- {$actie['omschrijving']} fietste {$actie['km']} km! {$edit}\n";
                } else {
                    $yaml .= "- Met onze actie \"{$actie['omschrijving']}\" kwamen er samen ook {$actie['km']} km bij {$edit}\n";
                }
            }
        }
        if ($params->mode == 'admin') {
            $yaml .= "\n\n<a href=\"./{$d['code']}/input/{$params->pass}\" class=\"btn btn-sm\">Voeg extra kilometers toe!</a>\n";
        }

        if ($params->mode == 'admin') {
            $yaml .= "\n\n# Video kiezen \n\n";

            list($videos, $videoMap) = ZPController::getVideos('', $params->code);
            $yaml .= '<p>';
            foreach ($videoMap as $videoCode => $videoInfo) {
                if ($videoCode == $d['video']) {
                    $extra = " btn-b";
                } else {
                    $extra = "";
                }
                $yaml .= "<a class=\"btn btn-sm{$extra}\" href=\"./{$d['code']}/changevideo/{$params->pass}/{$videoCode}\">{$videoInfo['naam']}</a>\n";
            }
            $yaml .= "</p>\n";
        }


        return array(
            'type' => 'html',
            'stats' => 'overview',
            'yaml+md' => $yaml,
            'menu' => ZPController::dynamicMenu($d),
        );
    }

    public function video($params)
    {
        $list = ZPController::getStatus($params->code);
        if (!$list) return ZPController::unknown();
        $d = $list['deelnemer'];
        list($videos, $videoMap) = ZPController::getVideos($params->domain, $params->code);

        $yaml = "---\n" .
            "title: Fiets mee met {$d['organisatie']}\n" .
            "colour: dark\n" .
            "---\n\n";
        $yaml .= '<p class="nomargins"><video autoplay controls loop muted style="width:100vw;height:90vh">';
        $videoUrl = isset($videoMap[$d['video']]) ? $videoMap[$d['video']]['url'] : $videos[0]['url'];
        $yaml .= "<source src=\"{$videoUrl}\" type=\"video/mp4\">";
        $yaml .= '<p>Video kan niet afgespeeld worden.</p>';
        $yaml .= '</video></p><hr />';
        $yaml .= '<p class="videos">';
        foreach ($videoMap as $videoCode => $videoInfo) {
            $yaml .= "<a class=\"btn btn-sm\" href=\"javascript:playVideo('" . $videoCode . "')\">{$videoInfo['naam']}</a>\n";
        }
        $yaml .= '</p>';

        $jscript = "\n";
        $jscript .= "let media = document.querySelector('video'); \n";
        $jscript .= "let domain = '{$params->domain}'; \n";
        $jscript .= "let wzc = '" . $list['deelnemer']['code'] . "'; \n";
        $jscript .= "let wzcFull = '" . $list['deelnemer']['organisatie'] . "'; \n";
        $jscript .= "let status = " . \json_encode($list) . "; \n";
        $jscript .= "let videoMap = " . \json_encode($videoMap) . "; \n";
        $jscript .= "update();\n";

        return array(
            'code' => 'video',
            'type' => 'html',
            'template' => 'video',
            'stats' => 'video',
            'yaml+md' => $yaml,
            'javascript_top' => array('./zlvrnpltn.js?cache=' . date('U')),
            'inlinescript' => $jscript,
        );
    }
}
