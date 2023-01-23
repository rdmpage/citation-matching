<?php

// Container info


require_once(dirname(__FILE__) . '/database/sqlite.php');
require_once(dirname(__FILE__) . '/compare.php');

//----------------------------------------------------------------------------------------
// ISSNs from title
function issn_from_title ($title)
{
	$issns = array();
	
	$sql = 'SELECT DISTINCT issn FROM issn WHERE title="' . addcslashes($title, '"') . '" COLLATE NOCASE;';

	//echo $sql;
	
	$result = do_query($sql);

	//print_r($result);

	foreach ($result as $row)
	{
		$issns[] = $row->issn;
	}
	
	if (count($issns) == 0)
	{
		// try approx match
		$like_title = preg_replace('/(\.\s+)/', '% ', $title);
		
		// echo $title;
		
		$sql = 'SELECT DISTINCT title, issn FROM issn WHERE title LIKE "' . addcslashes($like_title, '"') . '" COLLATE NOCASE;';

		// echo $sql;
	
		$result = do_query($sql);
		
		// get best match...
		$max_score = 0;
				
		foreach ($result as $row)
		{		
			$result = compare_common_subsequence(
				$title, 
				$row->title,
				false);

			if ($result->normalised[1] > 0.95)
			{
				// one string is almost an exact substring of the other
				if ($result->normalised[1] > $max_score)
				{
					$max_score = $result->normalised[1];
					$issns = array($row->issn);
				}
			}
		}
	
	
	}
	
	return $issns;
}

//----------------------------------------------------------------------------------------
// BHL title lookup based on ISSN
function get_bhl_title_from_issn ($issn)
{
	$titles = array();
	
	$sql = 'SELECT * FROM titleidentifiertxt WHERE identifiername="ISSN" AND identifiervalue="' . $issn . '";';

	$result = do_query($sql);
	
	foreach ($result as $row)
	{
		$titles[] = $row->titleid;
	}
	
	// hack to add missing titles	
	if (count($titles) == 0)
	{
		switch ($issn)
		{
			case '0035-8894':
				$titles[] = 11516;
				break;
			
			default:
				break;
		}
	}		
	
	return $titles;
}

//----------------------------------------------------------------------------------------
// BHL title from string (hack if we don't have identifier mapping
// Return as array as we may have > 1 TitleIDs for the same title
function get_bhl_title_from_text($text)
{
	$titles = array();
	
	switch ($text)
	{
		case 'Annali del Museo civico di storia naturale di Genova':
		case 'Ann. Mus. Stor. nat. Genova':
		case 'Ann. Mus. Sci. Stor. nat. Genova':
			$titles = array(7929);
			break;
	
		case 'Ann. Mag. nat. Hist (5)':
		case 'Ann. Mag. nat. Hist. (6)':
		case 'Ann. Mag. nat. Hist (6)':
		case 'Ann. Mag. nat. Hist. (7)':
		case 'Ann. Mag. nat. Hist. (8)':
			$titles = array(15774);
			break;
	
		case 'Ann. S. Afr. Mus.':
		case 'Ann.S.Afr.Mus.':		
			$titles = array(6928);
			break;

		case 'Ann. Transv. Mus.':		
			$titles = array(116503);
			break;
			
		case 'Ann. Soc. ent. Belg.':
			$titles = array(
				//11938,
				11933,
				);
			break;
			
		case 'Arkiv för zoologi':
		case 'Ark. zool':
		case 'Ark. Zool':		
			$titles = array(6919);
			break;
			
		case 'Austral. Orchid Rev.':
		case 'Australian Orchid Review':
			$titles = array(185262);
			break;
			
		case 'Biologia cent.-am. (Zool.) Lepid.-Heterocera':
			$titles = array(730);
			break;
			
		case 'Bull. Soc. ent. Fr.':
			$titles = array(8187);
			break;

		case 'Bull. Hill Mus. Witley':		
		case 'Bulletin of the Hill Museum : a magazine of lepidopterology':
			$titles = array(46541);
			break;
			
		case 'Boll. Mus. Torino':
			$titles = array(10776);
			break;
			
		case 'Bull. Brit. Orn. Club':
		case 'Bull. Brit. Orn. CI.':
		case 'Bull. Brit. Orn. Cl.':
			$titles = array(46639);
			break;
			
		case 'Bull. Soc. imp. Nat. Moscou':
			$titles = array(4951);
			break;
			
		case 'Bull. Mus. nat. Hist. Paris':
			$titles = array(68686);
			break;
			
		case 'Bull. Soc. portug. Sci. nat.':
		case 'Boll. Soc. Portug. Sci. nat.':
			$titles = array(169522);
			break;
			
		case 'Cat. Het. Mus. Oxford':
			$titles = array(31501);
			break;
			
		case 'Cat. Lep. Phal. Brit. Mus.':
		case 'Cat. Lep. Phalaenae Brit. Mus., Suppl.':
			$titles = array(9243);
			break;
			
		case 'Descr. Indian lep. Atkinson':
			$titles = array(5528);
			break;
			
		case 'Dtsch. ent. NatBibl.':
			$titles = array(47045);
			break;
						
		case 'Deutsche entomologische Zeitschrift Iris':
		case 'Dt. ent. Z. Iris':
		case 'Dt. ent. Zt. Iris':
			$titles = array(12260,12276);
			break;
			
		case 'Entomologist':
			$titles = array(9425);
			break;			
			
		case 'Ent. mon. Mag.':
			$titles = array(8646);
			break;
			
		case 'Ent. Nachr.':
		case 'Entomologische Nachrichten':
			$titles = array(9698);
			break;
	
		case 'Exot. Micr.':
		case 'Exotic Microlep.':
		case 'Exot. Microlepid.':
		case 'Exotic microlepidoptera':
		case 'Exotic Microlepidoptera.':
			$titles = array(9241);
			break;		
			
		case 'Fauna of British India, Moths':	
			$titles = array(100745);
			break;		
	
		case 'Genera Insectorum':
			$titles = array(45481);
			break;
			
		case 'Gross-Schmett. Erde':		
		case 'Die Grossschmetterlinge der Erde : eine systematische Bearbeitung der bis jetzt bekannten Grossschmetterlinge':
			$titles = array(62014);
			break;
			
		case 'Horae Soc. ent. ross.':
			$titles = array(87655);
			break;	
			
		case 'Icon. Pl. Formosan.':		
			$titles = array(1316);
			break;	
			
		case 'Internationale entomologische Zeitschrift':
		case 'Int. Ent. Zeitschr.':
			$titles = array(53710);
			break;			
			
		case 'Isis von Oken':
		case 'Isis, Leipzig':
			$titles = array(13271);
			break;
			
		case 'J. Dep. Agric. P. Rico':
			$titles = array(143341);
			break;
						
		case 'J. Straits Asiat. Soc.':
			$titles = array(64180);
			break;
			
		case 'Jahrbücher des Nassauischen Vereins für Naturkunde':
		case 'Jb. nassau. Verh. Natuurk':
		case 'Jb. nassau. Ver. Nat.':
			$titles = array(7007);
			break;
			
		case 'J.Bombay nat.Hist.Soc.':
			$titles = array(7414);
			break;
			
		case 'J. Linn. Soc. Lond. (Zool)':
		case 'J. Linn. Soc. London, Zool.':
			$titles = array(45411);
			break;
			
		case 'Joum. f. Orn.':
			$titles = array(47027);
			break;
			
		case 'List Specimens lepid. Insects Colln Br. Mus.':
		case 'List Spec. Lepid. Insects Colln Br. Mus.':
			$titles = array(58221);
			break;	
			
		case 'Mem. New York Bot. Gard.':
		case 'Memoirs of the New York Botanical Garden':				
			$titles = array(50489);
			break;	
			
		case 'Mitt. münch. ent. Ges.':
			$titles = array(15739);
			break;
			
		case 'Monogr. Culic.':
			$titles = array(58067);
			break;
			
		case 'Nota lepid.':
			$titles = array(79076);
			break;	
			
		case 'Novit, zool.':
		case 'Novit. zool.':
		case 'Novit. Zool.':
		case 'Novitates zoologicae':
		case 'Nov. Zool.':
			$titles = array(3882);
			break;
			
		case 'Philipp. J. Sci.':
		case 'Philippine J. Sci. (A)':
			$titles = array(50545);
			break;
			
		case 'Proc. U.S. natn. Mus.':		
			$titles = array(7519);
			break;	
			
		case 'Proc. zool. Soc. Lond.':
		case 'Proc. Zool. Soc. Lond.':
			$titles = array(44963);
			break;	
			
		case 'Psyche':
			$titles = array(11199);
			break;	
			
		case 'Quaest.ent.':
			$titles = array(119522);
			break;	
			
		case 'Revue Suisse de Zoologie':
		case 'Revue suisse de zoologie':
		case 'Revue suisse Zool.':
			$titles = array(8981);
			break;
			
			
		case 'Telopea':
			$titles = array(157010);
			break;
			
		case 'Tijdschr. Ent.':
			$titles = array(10088);
			break;
			
		case 'Trans. ent. Soc. Lond.':
		case 'Trans. Ent. Soc. Lond.':
			$titles = array(11516);
			break;			

		case 'Wien. ent. Mschr.':
			$titles = array(45022);
			break;

		case 'Zool Anz':
		case 'Zoologischer Anzeiger':
			$titles = array(8942);
			break;
		
		default:
			break;
	}	
	
	return $titles;
}



?>
