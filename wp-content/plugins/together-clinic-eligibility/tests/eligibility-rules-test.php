<?php
define('ABSPATH', '/tmp/');
function sanitize_text_field($s){ return trim(strip_tags((string)$s)); }
function apply_filters($h,$v){ return $v; }
function get_option($k,$d=false){ return $d; }
function wc_get_product(){ return false; }
$P = getenv("PLUGIN") ?: dirname(__DIR__);
require $P.'/includes/class-tc-variation-map.php';
require $P.'/includes/class-tc-dose-ladder.php';
require $P.'/includes/class-tc-eligibility-rules.php';
$pass=0;$fail=0;
function check($name,$cond,$extra=''){ global $pass,$fail; if($cond){$pass++; echo "PASS $name\n";} else {$fail++; echo "FAIL $name $extra\n";} }
function base($o=[]){ return array_merge(['ageBand'=>'18-74','dob'=>'1980-01-01','sex'=>'male','weightKg'=>100,'heightCm'=>180,'termsAgreed'=>true,'consentIdVideo'=>true,'gpConsentSCR'=>true,'consentLifestyle'=>true,'gpConsentShare'=>true,'conditions'=>['None of these apply'],'weightConditions'=>['None of these apply'],'diabetes'=>'none','userType'=>'new','ethnicity'=>'white'],$o); }
$R='TC_Eligibility_Rules';
// Today's two orders
$r=$R::evaluate(base(['sex'=>'female','pregnant'=>'no','breastfeeding'=>'no','conceive'=>'no','weightKg'=>75,'heightCm'=>160,'weightConditions'=>['My weight makes me anxious in social situations'],'couldConceive'=>'no']));
check('Order 279: BMI 29.3, social anxiety only -> not eligible',!$r['eligible'],json_encode($r));
$r=$R::evaluate(base(['sex'=>'female','pregnant'=>'no','breastfeeding'=>'no','conceive'=>'no','weightKg'=>75.3,'heightCm'=>165.1,'couldConceive'=>'no']));
check('Order 280: BMI 27.6, no condition -> not eligible',!$r['eligible'] && $r['bmi']==27.6,json_encode($r));
$r=$R::evaluate(base(['weightKg'=>100,'heightCm'=>180])); // 30.9
check('BMI 30.9 new -> eligible', $r['eligible'] && isset($r['flags']['triage_only']), json_encode($r));
$r=$R::evaluate(base(['weightKg'=>90,'heightCm'=>180,'weightConditions'=>["I've been diagnosed with high blood pressure"]])); // 27.8
check('BMI 27.8 + high BP -> eligible, list A flag',$r['eligible'] && isset($r['flags']['comorbidity_a']),json_encode($r));
$r=$R::evaluate(base(['weightKg'=>90,'heightCm'=>180,'weightConditions'=>['I have osteoarthritis']]));
check('BMI 27.8 + osteoarthritis -> eligible, list B flag',$r['eligible'] && isset($r['flags']['comorbidity_b']),json_encode($r));
$r=$R::evaluate(base(['weightKg'=>90,'heightCm'=>180,'diabetes'=>'pre']));
check('BMI 27.8 + pre-diabetes -> eligible',$r['eligible']);
$r=$R::evaluate(base(['weightKg'=>90,'heightCm'=>180,'weightConditions'=>['I have asthma']]));
check('BMI 27.8 + asthma only -> not eligible',!$r['eligible']);
$r=$R::evaluate(base(['weightKg'=>81,'heightCm'=>180,'ethnicity'=>'south asian','weightConditions'=>["I've been diagnosed with high blood pressure"]])); // 25.0
check('South Asian BMI 25 + BP -> not eligible (no ethnicity reduction)',!$r['eligible']);
$r=$R::evaluate(base(['gpConsentSCR'=>false]));
check('No SCR consent -> not eligible',!$r['eligible']);
$r=$R::evaluate(base(['consentIdVideo'=>false]));
check('No ID/video consent -> not eligible',!$r['eligible']);
$r=$R::evaluate(base(['gpConsentShare'=>false]));
check('No GP consent -> eligible with red flag',$r['eligible'] && isset($r['flags']['no_gp_consent']));
$r=$R::evaluate(base(['diabetes'=>'type1']));
check('Type 1 diabetes -> not eligible',!$r['eligible']);
foreach (['I have severe liver disease (for example cirrhosis)','I have severe stomach or bowel problems, including gastroparesis (very slow stomach emptying)','My weight gain is caused by a hormone condition or by a medicine I take','I have severe kidney disease or kidney failure','I have a history of pancreatitis'] as $c){
  $r=$R::evaluate(base(['conditions'=>[$c]])); check('Exclusion: '.substr($c,0,40),!$r['eligible']);
}
$r=$R::evaluate(base(['conditions'=>['I have had a bariatric operation'],'bariatricRecent'=>'no']));
check('Bariatric >6m -> eligible with flag',$r['eligible'] && isset($r['flags']['bariatric_history']));
$r=$R::evaluate(base(['sex'=>'female','pregnant'=>'no','breastfeeding'=>'no','conceive'=>'no','couldConceive'=>'yes','consentContraception'=>false]));
check('Could conceive, no contraception agreement -> not eligible',!$r['eligible']);
$r=$R::evaluate(base(['sex'=>'female','pregnant'=>'no','breastfeeding'=>'no','conceive'=>'no','couldConceive'=>'yes','consentContraception'=>true,'contraception'=>'pill']));
check('Could conceive, pill -> eligible with pill flag',$r['eligible'] && isset($r['flags']['contraception_pill']));
$r=$R::evaluate(base(['weightKg'=>0]));
check('Missing weight -> not eligible',!$r['eligible']);
$r=$R::evaluate(base(['bmi'=>35]));
check('Browser BMI mismatch flagged, server BMI used',$r['eligible'] && isset($r['flags']['bmi_mismatch']) && $r['bmi']==30.9, json_encode($r['flags']));
$r=$R::evaluate(base(['currentMedsList'=>'Gliclazide 80mg twice daily, sitagliptin','selectedTreatment'=>'mounjaro']));
check('Medicine flags: sulfonylurea and DPP-4',isset($r['flags']['med_sulfonylurea']) && isset($r['flags']['med_dpp4']), json_encode(array_keys($r['flags'])));
$r=$R::evaluate(base(['currentMedsList'=>'clarithromycin','selectedTreatment'=>'foundayo']));
check('Foundayo cap flag',isset($r['flags']['med_foundayo_cap']));
$r=$R::evaluate(base(['currentMedsList'=>'clarithromycin','selectedTreatment'=>'mounjaro']));
check('Foundayo cap flag raised whatever product is preferred',isset($r['flags']['med_foundayo_cap']));
$r=$R::evaluate(base(['currentMedsList'=>'Allium supplement']));
check('No false "alli" match inside another word',!isset($r['flags']['med_other_weightloss']));
// Switchers
$ld = (new DateTime('-10 days'))->format('Y-m-d');
$r=$R::evaluate(base(['userType'=>'switching','weightKg'=>78,'heightCm'=>180,'startWeightKg'=>100,'lastDoseDate'=>$ld])); // now 24.1, start 30.9
check('Transfer: now 24.1 -> not eligible (must be above 25, as Deltera)',!$r['eligible'], json_encode($r));
$r=$R::evaluate(base(['userType'=>'switching','weightKg'=>84,'heightCm'=>180,'startWeightKg'=>100,'lastDoseDate'=>$ld])); // 25.9
check('Transfer: now 25.9, start 30.9 -> eligible with proof flag',$r['eligible'] && isset($r['flags']['proof_required']) && isset($r['flags']['last_dose']), json_encode($r));
$old=(new DateTime('-120 days'))->format('Y-m-d');
$r=$R::evaluate(base(['userType'=>'switching','weightKg'=>84,'heightCm'=>180,'startWeightKg'=>100,'lastDoseDate'=>$old])); // 25.9, >3 months
check('Last dose 120 days ago, BMI 25.9 -> assessed as new -> not eligible',!$r['eligible'], json_encode($r));
$r=$R::evaluate(base(['userType'=>'switching','weightKg'=>100,'heightCm'=>180,'startWeightKg'=>110,'lastDoseDate'=>$old])); // 30.9
check('Last dose 120 days ago, BMI 30.9 -> new pathway, eligible, flagged',$r['eligible'] && $r['pathway']==='new' && isset($r['flags']['restart_over_3m']), json_encode($r));
$r=$R::evaluate(base(['userType'=>'switching','weightKg'=>65,'heightCm'=>180,'startWeightKg'=>100,'lastDoseDate'=>$ld])); // 20.1
check('Transfer: now 20.1 -> not eligible',!$r['eligible']);
$r=$R::evaluate(base(['userType'=>'switching','weightKg'=>60,'heightCm'=>180,'startWeightKg'=>100,'lastDoseDate'=>$ld])); // 18.5
check('Transfer: now 18.5 -> not eligible',!$r['eligible']);
$r=$R::evaluate(base(['userType'=>'switching','weightKg'=>78,'heightCm'=>180,'startWeightKg'=>85,'lastDoseDate'=>$ld])); // start 26.2
check('Transfer: start BMI 26.2 -> not eligible',!$r['eligible']);
$r=$R::evaluate(base(['userType'=>'switching','weightKg'=>78,'heightCm'=>180,'startWeightKg'=>0,'lastDoseDate'=>$ld]));
check('Transfer: no start weight -> not eligible',!$r['eligible']);
$r=$R::evaluate(base(['userType'=>'switching','weightKg'=>78,'heightCm'=>180,'startWeightKg'=>100,'lastDoseDate'=>'2099-01-01']));
check('Transfer: future last dose -> not eligible',!$r['eligible']);
// Doses
$D='TC_Dose_Ladder';
$x=$D::propose_start_dose('mounjaro','10mg','wegovy',5); check('Mounjaro 10mg -> Wegovy: 0.25mg', $x['dose']==='0.25mg', json_encode($x));
$x=$D::propose_start_dose('wegovy','2.4mg','mounjaro',5); check('Wegovy 2.4 -> Mounjaro: 2.5mg', $x['dose']==='2.5mg', json_encode($x));
$x=$D::propose_start_dose('wegovy','2.4mg','wegovy-tablets',7); check('Wegovy 2.4 inj -> tablets 25mg (SmPC)', $x['dose']==='25mg');
$x=$D::propose_start_dose('wegovy','1.7mg','wegovy-tablets',7); check('Wegovy 1.7 inj -> tablets 1.5mg', $x['dose']==='1.5mg');
$x=$D::propose_start_dose('wegovy-tablets','25mg','wegovy',1); check('Tablets 25 -> inj 2.4 (SmPC)', $x['dose']==='2.4mg');
$x=$D::propose_start_dose('mounjaro','7.5mg','mounjaro',3); check('Same, 3 days: continue 7.5', $x['dose']==='7.5mg');
$x=$D::propose_start_dose('mounjaro','7.5mg','mounjaro',20); check('Same, 20 days: step down 5mg', $x['dose']==='5mg');
$x=$D::propose_start_dose('mounjaro','7.5mg','mounjaro',40); check('Same, 40 days: restart 2.5', $x['dose']==='2.5mg');
$x=$D::propose_start_dose('foundayo','9mg','foundayo',3); check('Foundayo same, 3 days: continue 9', $x['dose']==='9mg');
$x=$D::propose_start_dose('foundayo','9mg','foundayo',6); check('Foundayo same, 6 days: step down 5.5', $x['dose']==='5.5mg');
$x=$D::propose_start_dose('foundayo','9mg','foundayo',10); check('Foundayo same, 10 days: restart 0.8', $x['dose']==='0.8mg');
$x=$D::propose_start_dose('wegovy-tablets','9mg','wegovy-tablets',10); check('Wegovy tablets same, 10 days: step down 4', $x['dose']==='4mg');
$x=$D::propose_start_dose('mounjaro','7.5mg','mounjaro',15); check('Mounjaro same, 15 days: step down 5', $x['dose']==='5mg');
$x=$D::propose_start_dose('other','','foundayo',10); check('Other source -> Foundayo 0.8', $x['dose']==='0.8mg');
$x=$D::propose_start_dose('mounjaro','7.5mg','mounjaro',null); check('Unknown last dose -> starter', $x['dose']==='2.5mg');
$r=$R::evaluate(base(['weightKg'=>87.32,'heightCm'=>180])); // 26.95 raw
check('BMI 26.95 does not round up into eligibility',!$r['eligible'], json_encode($r));
$r=$R::evaluate(base(['sex'=>'female','weightKg'=>100,'heightCm'=>170]));
check('Female with pregnancy answers missing -> not eligible',!$r['eligible']);
$r=$R::evaluate(base(['currentMedsList'=>'amlodipine 5mg','selectedTreatment'=>'mounjaro']));
check('Foundayo BP flag raised even if Mounjaro preferred',isset($r['flags']['med_foundayo_bp']));
echo "\n$pass passed, $fail failed\n"; exit($fail?1:0);
