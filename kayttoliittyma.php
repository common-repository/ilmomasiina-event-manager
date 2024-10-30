<?php

add_shortcode('ilmomasiina' , 'ilmomasiinan_kayttoliittyma');


// VARSINAINEN OHJELMA

function ilmomasiinan_kayttoliittyma() {
	$tuloste = '';
	
	if (isset($_GET['tapahtuma'])) {
		$tuloste .= tulosta_tapahtuma($_GET['tapahtuma']);
	} else {
		$tuloste .= tulosta_tapahtumalista();
	}
	
	return $tuloste;
}



// TAPAHTUMALISTAUS
function tulosta_tapahtumalista() {
	$tuloste = '';
	
	// Uusi tapahtuma -linkki
	if (current_user_can('edit_posts')) {
		$tuloste .= '<a href="'.admin_url('post-new.php?post_type=tapahtumat').'" target="_blank">Luo uusi tapahtuma</a><br />';
		$tuloste .= '<a href="'.admin_url('edit.php?post_type=tapahtumat').'" target="_blank">Muokkaa tapahtumiasi</a><br /><br />';
	}
	
	$tapahtumat = hae_tapahtumat();
	
	$ilmoauki = array();
	$ilmotulossa = array();
	$ilmokiinni = array();
	// Lopulta alkuperäseen arrayhin jää jäljelle menneet tapahtumat
	
	foreach ($tapahtumat as $key => $tapahtuma) {
		$id = $tapahtuma->ID;
		$ilmoaika = get_post_meta($id, '_ilmoaika', true);
		$ilmonloppuaika = get_post_meta($id, '_ilmonloppuaika', true);
		$tapahtumanaika = get_post_meta($id, '_tapahtumanaika', true);
		$yksityinen = get_post_meta($id, '_yksityinen_tapahtuma', true);
    if ( $yksityinen ) {
			unset($tapahtumat[$key]);
      continue;
    }
    
    
		$nyt = current_time('timestamp');
		
		if ($ilmoaika > $nyt) {
			$ilmotulossa[] = $tapahtuma;
			unset($tapahtumat[$key]);
			continue;
		}
		
		if ($ilmoaika < $nyt && $ilmonloppuaika > $nyt && $tapahtumanaika > $nyt) {
			$ilmoauki[] = $tapahtuma;
			unset($tapahtumat[$key]);
			continue;
		}
		
		if ($ilmoaika < $nyt && $ilmonloppuaika < $nyt && $tapahtumanaika > $nyt) {
			$ilmokiinni[] = $tapahtuma;
			unset($tapahtumat[$key]);
			continue;
		}
	}
	
	if ($ilmoauki) {
		$tuloste .= '<h3>Ilmo auki:</h3>';
		$tuloste .= tulosta_tapahtuman_alilista($ilmoauki);
	}
	
	if ($ilmotulossa) {
	$tuloste .= '<h3>Ilmo tulossa:</h3>';
	$tuloste .= tulosta_tapahtuman_alilista($ilmotulossa);
	}
	
	if ($ilmokiinni) {
	$tuloste .= '<h3>Ilmo sulkeutunut:</h3>';
	$tuloste .= tulosta_tapahtuman_alilista($ilmokiinni);
	}
	
	if ($tapahtumat) {
	$tuloste .= '<h3>Menneet tapahtumat:</h3>';
	$tuloste .= tulosta_tapahtuman_alilista($tapahtumat);
	}
	
	return $tuloste;
}

// Tulosta listana tapahtumat
function tulosta_tapahtuman_alilista($tapahtumat) {
	$tuloste = '';
	
	foreach ($tapahtumat as $tapahtuma) {
		$tuloste .= '<a href="'.get_permalink($tapahtuma->ID).'">'.$tapahtuma->post_title.'</a>';
		
		if (get_post_meta($tapahtuma->ID, '_maxosallistujat', true) <= count(hae_ilmot($tapahtuma->ID)) && get_post_meta($tapahtuma->ID, '_maxosallistujat', true)>0 && current_time('timestamp') < get_post_meta($tapahtuma->ID, '_tapahtumanaika', true)) {
			$tuloste .= ' <span style="font-weight:bold;">Täynnä!</span>';
		}
		
		if ($tapahtuma->post_author == get_current_user_id()) {
			$tuloste .= '<a style="float: right;" target="_blank" href="'.get_edit_post_link($tapahtuma->ID).'">Muokkaa tapahtumaa</a>';
		}
		$tuloste .= '<br />';
	}
	
	return $tuloste;
}


// HAE TAPAHTUMAT
function hae_tapahtumat() {
	$args = array(
		'post_type'        => 'tapahtumat',
		'post_status'      => 'publish',
		'meta_key'         => '_tapahtumanaika',
		'orderby'          => 'meta_value',
		'order'            => 'DESC',
		'posts_per_page'   => 500,
	);
	
	$tapahtumat = get_posts($args);
	
	return $tapahtumat;
}


add_filter('the_content', 'tulosta_tapahtuma' , 11, 1);

// YKSITTÄINEN TAPAHTUMA
function tulosta_tapahtuma($content) {
	global $post;
  
  if ($post->post_type != 'tapahtumat') return $content;
  
  $id = $post->ID;
	
	
	
	
	$tuloste = '<div class="tapahtuma">';
	
	if ($post->post_author == get_current_user_id()) {
		$tuloste .= '<p><a target="_blank" href="'.get_edit_post_link($post->ID).'">Muokkaa tapahtumaa tai katsele osallistujia</a></p>';
	}
	
	
	//$tuloste .= get_the_post_thumbnail( $post->ID, 'large' ).'<br /><br />';
	
	
	$tuloste .= '<table>

<tr>
<td>Päivämäärä:</td>
<td>'.date('d.m.Y H:i',get_post_meta($id, '_tapahtumanaika', true)).'</td>
</tr>

<tr>
<td>Ilmoittautuminen auki:</td>
<td>'.date('d.m. H:i',get_post_meta($id, '_ilmoaika', true));
	
	if (current_time('timestamp')<get_post_meta($id, '_ilmoaika', true)) {
		$ero = get_post_meta($id, '_ilmoaika', true) - current_time('timestamp');
		$paivat = floor($ero/60/60/24);
		$tunnit = floor(($ero-$paivat*60*60*24)/60/60);
		$minuutit = floor(($ero-$paivat*60*60*24-$tunnit*60*60)/60);
		$sekunnit = floor($ero-$paivat*60*60*24-$tunnit*60*60-$minuutit*60);
    $tuloste .= ' <i>Aikaa ilmon alkuun: </i>' .($paivat?$paivat.'d, ':'').($tunnit?$tunnit.'h, ':'').($minuutit?$minuutit.'min':'').($ero<(60*60)?', '.$sekunnit.'s' : '' );
  }
  
  if (get_post_meta($id, '_tapahtumanaika', true) != get_post_meta($id, '_ilmonloppuaika', true)) {
    $tuloste .= '<br /> - <br />'.date('d.m. H:i',get_post_meta($id, '_ilmonloppuaika', true));
  }
	
	$tuloste .= '
</td>
</tr>';
	
	
	if (get_post_meta($id, '_maxosallistujat', true)) {
		$tuloste .= '
  <tr>
	<td>Osallistujaraja:</td>
	<td>'.get_post_meta($id, '_maxosallistujat', true).'</td>
	</tr>';
	}
	
	if (get_post_meta($id, '_maxosallistujat', true)) {
		$tuloste .= '
	<tr>
	<td>Varasijat käytössä:</td>
	<td>'.(get_post_meta($id, '_varasijat', true)?'Kyllä':'Ei').'</td>
	</tr>';
	}
	
	$tuloste .= '

</table><br />';
	
	
	$tuloste .= $content;
	
	$tuloste .= '<hr>';
  
  //Jos kyseessä on ensiilmoittautuminen, luo ilmo tietokantaan.
  $md5 = false;
  if ($_GET['ilmoittaudu']) {
    $md5 = aloita_ilmoittautuminen($id);
    $tuloste .= '<script>window.onbeforeunload = function() { return "Olet sijalla '.hae_sijanumero($id,$md5).', mutta olet jättämässä ilmoittautumisen käyttämättä. Tätä ei voi perua. Oletko varma?"; }; </script>';
  }
  
  if (isset($_POST['ilmo_nonce'])) {
    $tuloste .= '<p>'.tallenna_ilmo($id, $_POST['md5']).'</p>';
  }
  
  if (isset($_GET['peru']) && get_post_meta($id, '_salli_peruutus', true)) {
    $tuloste .= peruuta_osallistuminen($id, $_GET['peru']);
  }
  
  if (get_post_meta($id, '_tapahtumanaika', true) - current_time('timestamp') > 0 && !get_post_meta($id, '_piilota_ilmomaara', true)) {
    $tuloste .= tapahtuman_osallistujalista($id);
  }
  
  // Jos kyseessä on ensiilmoittautuminen, näytä ilmoittautumislomake
  if ($md5) {
    $tuloste .= ilmolomake($id, $md5, false);
  }
  // Jos kyseessä on muokkaustilanne
  if (isset($_GET['muokkaa'])) {
    $tuloste .= ilmolomake($id, $_GET['muokkaa'], false);
  }
  
  
  if (!isset($_GET['muokkaa']) && !$md5) {	
    
    if ( // Hemmetin pitkä if klausuuli alkaa
      
      current_time('timestamp') > get_post_meta($id, '_ilmoaika', true) && 
      current_time('timestamp') < get_post_meta($id, '_ilmonloppuaika', true)&&  
      (
        get_post_meta($id, '_varasijat', true) || 
        get_post_meta($id, '_maxosallistujat', true) > count(hae_ilmot($id)) || 
        !(get_post_meta($id, '_maxosallistujat', true)>0)
      ) 
    ) { // Hemmetin pitkä if klausuuli loppuu, ja itse if sisältö alkaa
      
      
      $url = get_permalink($id);
      $url = $url . (strpos($url, '?') ? '&' : '?') . 'ilmoittaudu=true#ilmolomakediv';    
      $tuloste .= '<a href="'.$url.'" style="-webkit-appearance: button; -moz-appearance: button; appearance: button; text-decoration: none; color: initial;">Ilmoittaudu</a>';
      
    } else {
      
      if ($post->post_author == get_current_user_id() && get_post_meta($id, '_tapahtumanaika', true) - time() > 0 ) {
        $url = get_permalink($id);
        $url = $url . (strpos($url, '?') ? '&' : '?') . 'ilmoittaudu=true#ilmolomakediv';    
        $tuloste .= '<p>Ilmo on kiinni, mutta koska olet tapahtuman moderaattori, niin tässä kuitenkin sinulle ilmolomake:</p>';
        $tuloste .= '<a href="'.$url.'" style="-webkit-appearance: button; -moz-appearance: button; appearance: button; text-decoration: none; color: initial;">Ilmoittaudu</a>';
      }
      
    }
  }
  $tuloste .= '</div>';
  
  
  if ($post->post_author == get_current_user_id()) {
    ob_start();
    echo '<hr style="margin-bottom:0.5em;" /> <p>Tämä näkyy vain tapahtuman ylläpitäjälle:</p> <h2>Osallistujat:</h2><br />';
    tapahtumaan_ilmonneet_metabox();
    $tuloste .= ob_get_clean();
  }
  
	return $tuloste;
}



// Listataan jo ilmottautuneet
function tapahtuman_osallistujalista($id) {
	$tuloste = '<h4>Ilmoittautuneet:</h4> ';
  $ilmot = hae_ilmot($id);
  
  if (get_post_meta($id, '_piilota_ilmolista', true)) return $tuloste. '<br />'.count($ilmot).' kpl <br />';
  if (is_array($ilmot)) {
    
    
    $tuloste .= '<ol>';
    $i = 0;
    foreach ($ilmot as $ilmo) {
      $nimiteksti = (isset($ilmo['anonyymi']) && $ilmo['anonyymi']==true?'<i>Anonyymi</i>':$ilmo['nimi']);
      $nimiteksti = (isset($ilmo['ensiilmo']) && $ilmo['ensiilmo']==true?'<i>Täyttämässä lomaketta</i>':$nimiteksti);
      $tuloste .= '<li>'.$nimiteksti;
      
      if (get_post_meta($id, '_salli_muokkaus', true) && $ilmo['ensiilmo']!=true) {
        
        $url = get_permalink($id);
        $url = $url . (strpos($url, '?') ? '&' : '?') . 'muokkaa='.$ilmo['md5'];        
        
        $tuloste .= ' <a href="'.$url.'#muokkaus">Muokkaa</a>';
      }
      
      
      $i++;
      if ($i == get_post_meta($id, '_maxosallistujat', true)) {
        $tuloste .= '</li></ol><hr /><h5>Varasijat:</h5><ol>';
      } else {
        $tuloste .= '</li>';
      }
    }
    $tuloste .= '</ol>';
  }
  
  return $tuloste;
}

// Ilmottautumislomake
function ilmolomake($id, $md5, $paivitys=false) {
  
  $aika = current_time('timestamp');
  if (   
    ($aika < get_post_meta($id, '_ilmoaika', true)   
     ||
     $aika > get_post_meta($id, '_ilmonloppuaika', true)  
    ) 
    && 
    get_post_field( 'post_author', $id ) != get_current_user_id() 
  ) {
    return 'Tallennus epäonnistui.';
  }
  
	$tuloste = '';
	$tuloste .= '<div id="ilmolomakediv">';
	$tuloste .= '<hr />';
	$paivitys = (isset($_GET['muokkaa']) ? true: false);
	if ($paivitys==false) {
		$tuloste .= '<h3>Ilmoittautumislomake:</h3>';
    $tuloste .= '<p><span style="color:red; font-weight:bold;">Olet sijalla '.hae_sijanumero($id, $md5).'. Jäljellä oleva aika tämän lomakkeen täyttöön: <span id="time">10:00</span></span><br /><i>Huom. Aika on suuntaa antava. Aikaa on 10min ilmoittautumisen alusta.</i></p>';
    $tuloste .= '
<script>
function startTimer(duration, display) {
    var timer = duration, minutes, seconds;
    setInterval(function () {
        minutes = parseInt(timer / 60, 10)
        seconds = parseInt(timer % 60, 10);

        minutes = minutes < 10 ? "0" + minutes : minutes;
        seconds = seconds < 10 ? "0" + seconds : seconds;

        display.textContent = minutes + ":" + seconds;

        if (--timer < 0) {
            timer = duration;
        }
    }, 1000);
}

window.onload = function () {
    var fiveMinutes = 60 * 10,
        display = document.querySelector("#time");
    startTimer(fiveMinutes, display);
};
</script>';

	} else {
		$tuloste .= '<h3 id="muokkaus">Päivitä aiempaa vastausta</h3>';
		$tuloste .= '<p>Myös vanhat tiedot säilyvät tietokannassa väärinkäytösten ja virheiden estämiseksi.</p>';
    
    
    if (get_post_meta($id, '_salli_peruutus', true)) {
      $url = get_permalink($id);
      $url = $url . (strpos($url, '?') ? '&' : '?') . 'peru='.$_GET['muokkaa'];    
      
      $tuloste .= '
<script>
function peruuta() {
formi = document.getElementById("peruutusform");
formi.style.display = "inline-block";
}
</script>
';
      
      $tuloste .= '<p><a href="javascript:peruuta()">Peru ilmottautuminen</a></p>';
      $tuloste .= '<form method="post" action="'.$url.'" id="peruutusform" style="display:none; margin-bottom: 3em;">';
      $tuloste .= '<p>kolme plus neljä = ';
      $tuloste .= '<input name="lasku" style="width: 2em;"/></p>';
      $tuloste .= '<input type="submit" value="Peru ilmoittautuminen">';
      $tuloste .= '</form>';
    }
    
	}
	
	$tuloste .= '<form method="post" action="'.get_permalink($id).'" onsubmit="window.onbeforeunload = null;">';
	
  $ilmoaika = current_time('timestamp');
  
  $tuloste .= '<input type="hidden" name="md5" id="md5" value="'.$md5.'" />';
  
	$tuloste .= '<input type="hidden" name="ilmo_nonce" id="ilmo_nonce" value="' . wp_create_nonce( plugin_basename(__FILE__).'ilmon_nonce'.$ilmoaika ) . '" />';
	$tuloste .= '<input type="hidden" name="ilmo_aika" id="ilmo_aika" value="'. $ilmoaika .'" />';
	$tuloste .= ( $paivitys ? '<input type="hidden" name="paivitys" value="'.$_GET['muokkaa'].'">' : '' );
	
	$tuloste .= '<label class="ilmo_ohje" for="nimi">Nimi: *</label><br />';
	$tuloste .= '<input class="ilmoteksti" type="text" name="nimi" id="nimi" required/><br />';
	if (!get_post_meta($id, '_piilota_ilmolista', true)) {
		$tuloste .= '<input class="ilmomonivalinta" type="checkbox" name="anonyymi" id="anonyymi" value="1"/>';
		$tuloste .= ' <label class="anonyymi" for="anonyymi">Piilota nimi julkisesta listasta</label><br /><br />';
	}
	
  $kentat = get_post_meta($id, '_kentat', true);
  if (!$kentat) $kentat = array();
  
  foreach ($kentat as $key => $kentta) {
    if ($kentta['tyyppi'] == 'ohje') {
      $tuloste .= '<p>'.$kentta['ohje'].'</p>';
    }
    
    if ($kentta['tyyppi'] == 'teksti') {
      $tuloste .= '<p><label class="ilmo_ohje" for="'.$key.'_kentta">'.$kentta['ohje'].' '.($kentta['pakollinen']?'*':'').'</label><br />';
      $tuloste .= '<input class="ilmoteksti" type="text" name="'.$key.'" id="'.$key.'_kentta" '.($kentta['pakollinen']?'required':'').' /></p>';
    }
    
    if ($kentta['tyyppi'] == 'email') {
      $tuloste .= '<p><label class="ilmo_ohje" for="'.$key.'_kentta">'.$kentta['ohje'].' '.($kentta['pakollinen']?'*':'').'</label><br />';
      $tuloste .= '<input class="ilmoteksti" type="email" name="'.$key.'" id="'.$key.'_kentta" '.($kentta['pakollinen']?'required':'').' /></p>';
    }
    
    if ($kentta['tyyppi'] == 'isoteksti') {
      $tuloste .= '<p><label class="ilmo_ohje" for="'.$key.'_kentta">'.$kentta['ohje'].' '.($kentta['pakollinen']?'*':'').'</label><br />';
      $tuloste .= '<textarea style="min-width: 300px; min-height: 150px;" class="ilmoisoteksti" type="text" name="'.$key.'" id="'.$key.'_kentta" '.($kentta['pakollinen']?'required':'').'> </textarea></p>';
    }
    
    if ($kentta['tyyppi'] == 'valinta') {
      $tuloste .= '<p>'.$kentta['ohje'].($kentta['pakollinen']?'*':'').'<br />';
      $i = 0;
      foreach ($kentta['vaihtoehdot'] as $vaihtoehto) {
        $tuloste .= '<input type="radio" class="ilmomonivalinta" name="'.$key.'" id="'.$key.'_kentta_'.$i.'" value="'.$i.'" '.($kentta['pakollinen']?'required':'').'>';
        $tuloste .= '<label for="'.$key.'_kentta_'.$i.'">'.$vaihtoehto.'</label><br />';
        $i++;
      }
      $tuloste .= '</p>';
    }
    
    if ($kentta['tyyppi'] == 'monivalinta') {
      $tuloste .= '<p>'.$kentta['ohje'].($kentta['pakollinen']?'*':'').'<br />';
      $i = 0;
      foreach ($kentta['vaihtoehdot'] as $vaihtoehto) {
        $tuloste .= '<input type="checkbox" class="ilmovalinta" name="'.$key.'_'.$i.'" id="'.$key.'_kentta_'.$i.'" value="kylla" >';
        $tuloste .= '<label for="'.$key.'_kentta_'.$i.'">'.$vaihtoehto.'</label><br />';
        $i++;
      }
      $tuloste .= '</p>';
    }
  }
  
  
	$tuloste .= '<input type="submit" value="Lähetä" />';
	$tuloste .= '</form>';
	$tuloste .= '</div>';
	
	
	return $tuloste;
  
  
	
}

            

// TALLENNA ILMO 

function tallenna_ilmo($id, $md5) {
  
	
  $aika = current_time('timestamp');
  if (   
    ($aika < get_post_meta($id, '_ilmoaika', true)   
     ||
     $aika > get_post_meta($id, '_ilmonloppuaika', true)  
    ) 
    && 
    get_post_field( 'post_author', $id ) != get_current_user_id() 
  ) {
    return 'Tallennus epäonnistui.';
  }
	
  $kentat = get_post_meta($id, '_kentat', true);
  if (!$kentat) $kentat = array();
	
	
	$paivitys = (isset($_POST['paivitys']) ? $_POST['paivitys'] : false );
  
  if ($paivitys != false && get_post_meta($id, '_salli_muokkaus', true) != true ) return 'Tallennus epäonnistui';
  
	$vastaus = valitse_ilmo($id, $md5);
	$vastaus['nimi'] = sanitize_text_field($_POST['nimi']);
	$vastaus['anonyymi'] = ($_POST['anonyymi']==1?true:false);
  $vastaus['ensiilmo'] = false;
	
  
  foreach ($kentat as $key => $kentta) {
    if ($kentta['tyyppi']=='ohje') continue;
    $vastaus[$kentta['ohje']] = sanitize_text_field($_POST[$key]);
    
    if ($kentta['tyyppi']=='monivalinta') {
      $vastaus[$kentta['ohje']] = '';
      foreach ($kentta['vaihtoehdot'] as $vaihtoehtokey => $vaihtoehto) {
        $vastaus[$kentta['ohje']] .= ($_POST[$key.'_'.$vaihtoehtokey] == 'kylla' ? $vaihtoehto.', ' : '');
      }
    }
    
    if ($kentta['tyyppi']=='valinta') {
      $vastaus[$kentta['ohje']] = $kentta['vaihtoehdot'][$_POST[$key]];
		}
	}

		
	
	$aiempi = valitse_ilmo($id, $md5);
	if (!$aiempi || strlen($aiempi['md5']) != 32 ) return 'Tallennus epäonnistui.';
	
	if ($paivitys) {
		$vastaus['ennenmuokkausta'] = $aiempi;
    $vastaus['muokkausaika'] = current_time('timestamp');
    $onnistuko = update_post_meta($id, '_yksittainenilmo', $vastaus, $aiempi);
    
	}
	
	
	if (!$paivitys) {
		$onnistuko = update_post_meta($id, '_yksittainenilmo', $vastaus, $aiempi);
	}
	
	$tuloste .= ($onnistuko? 'Tiedot lisätty onnistuneesti!' : 'Ilmoittautuminen epäonnistui.');
	
  return $tuloste;
}


// LUODAAN ILMOPAIKKA ILMOITTAUTUMISEN KLIKKAAMISEN JÄLKEEN
function aloita_ilmoittautuminen($id) {

  $aika = current_time('timestamp');
  
  if (   
    ($aika < get_post_meta($id, '_ilmoaika', true)   
     ||
     $aika > get_post_meta($id, '_ilmonloppuaika', true)  
    ) 
    && 
    get_post_field( 'post_author', $id ) != get_current_user_id() 
  ) {
    return 'Tallennus epäonnistui.';
  }
  
  
  $kentat = get_post_meta($id, '_kentat', true);
  
  $md5 = md5($aika.random_int(0, 100000));
  $vastaus = array(
    'ilmoaika' => $aika,
    'ensiilmo' => true,
    'md5' => $md5,
  );
  
  $ok = add_post_meta($id, '_yksittainenilmo', $vastaus);
  if ($ok) return $md5;
  return false;
}

// PERUTAAN OSALLISTUMINEN
function peruuta_osallistuminen($id, $md5) {
	
  if (get_post_meta($id, '_salli_peruutus', true) != true || $_POST['lasku'] != 7) return '<p style="color: red">Peruutus ei onnistunut.</p>';
  
	$ilmo = valitse_ilmo($id, $md5);
  $alkuperanen = $ilmo;
	if ( $ilmo ) {
		$ilmo['peruuttanut'] = true;
		$ok1 = delete_post_meta($id, '_yksittainenilmo', $alkuperanen);
    $ok2 = add_post_meta($id, '_yksittainenperuutus', $ilmo);
		
		$tuloste = ($ok1 && $ok2 ? '<p>Ilmottautuminen peruttu.</p>' : 'Jokin meni pieleen..');
	} else {
		$tuloste = '<p>Ilmottautumista ei löydetty, eikä sitä poistettu.</p>';
	}
	return $tuloste;

}

// Valitse ilmo
function valitse_ilmo($id, $md5) {
	$ilmot = get_post_meta( $id, '_yksittainenilmo', false);
	foreach ($ilmot as $ilmo) {
		if ($ilmo['md5'] == $md5) return $ilmo;
	}
	return false;
}

// Hae pelkät voimassa olevat ilmot oikeassa järjestyksessä
function hae_ilmot($id) {
  poista_vanhentuneet_ensiilmot($id);
	$ilmot = get_post_meta( $id, '_yksittainenilmo', false);
  $ilmot = jarjesta_ilmot_aika($ilmot);
  return $ilmot;
}

// Hae peruuttaneet ilmot oikeassa järjestyksessä
function hae_peruuttaneet($id) {
	$ilmot = get_post_meta( $id, '_yksittainenperuutus', false);
  $ilmot = jarjesta_ilmot_aika($ilmot);
  return $ilmot;
}

// Hae sijanumero
function hae_sijanumero($id, $md5) {
  $ilmot = hae_ilmot($id);
  $i=0;
  foreach ($ilmot as $ilmo) {
    $i++;
    if ($ilmo['md5'] == $md5) return $i;
  }
  return false;
}