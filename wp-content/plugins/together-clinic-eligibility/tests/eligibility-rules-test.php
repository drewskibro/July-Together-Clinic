<?php
define('ABSPATH', '/tmp/');
function esc_html($s){ return htmlspecialchars((string)$s); }
function sanitize_text_field($s){ return trim(strip_tags((string)$s)); }
function apply_filters($h,$v){ return $v; }
function get_option($k,$d=false){ return $d; }
function wc_get_product(){ return false; }
$P = getenv("PLUGIN") ?: dirname(__DIR__);
require $P.'/includes/class-tc-variation-map.php';
require $P.'/includes/class-tc-dose-ladder.php';
require $P.'/includes/class-tc-eligibility-rules.php';
require $P.'/reorder/includes/class-tc-reorder-rules.php';
$pass=0;$fail=0;
function check($name,$cond,$extra=''){ global $pass,$fail; if($cond){$pass++; echo "PASS $name\n";} else {$fail++; echo "FAIL $name $extra\n";} }
function base($o=[]){ return array_merge(['ageBand'=>'18-85','dob'=>'1980-01-01','sex'=>'male','weightKg'=>100,'heightCm'=>180,'termsAgreed'=>true,'consentIdVideo'=>true,'gpConsentSCR'=>true,'consentLifestyle'=>true,'gpConsentShare'=>true,'conditions'=>['None of these apply'],'weightConditions'=>['None of these apply'],'diabetes'=>'none','userType'=>'new','ethnicity'=>'white'],$o); }
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
// Rules WM-2026-10-v2: age 18 to 85 by date of birth (rule S1), ages 75 to 85 (rule S1A); v3 adds Orlistat.
check('Rules version is WM-2026-10-v3', $R::RULES_VERSION==='WM-2026-10-v3');
$r=$R::evaluate(base()); check('Result records rules version v3', $r['rules_version']==='WM-2026-10-v3');
function dob_years_ago($y,$plus_days=0){ return (new DateTime('today'))->modify("-$y years")->modify(($plus_days>=0?'+':'').$plus_days.' days')->format('Y-m-d'); }
function s1a($o=[]){ return array_merge(['s1aFalls'=>'no','s1aFracture'=>'no','s1aMedsCount'=>'under-5','s1aBpWater'=>'no','s1aKidneyTest'=>'within-12m','s1aEgfrDate'=>'','s1aEgfrResult'=>'','gpName'=>'Test Surgery','s1aPrisma'=>['limitActivities'=>'no','needHelp'=>'no','stayHome'=>'no','countOnSomeone'=>'no','walkingAid'=>'no']],$o); }
$r=$R::evaluate(base(['dob'=>dob_years_ago(18)])); check('DOB: 18th birthday today -> may proceed',$r['eligible'],json_encode($r));
$r=$R::evaluate(base(['dob'=>dob_years_ago(18,1)])); check('DOB: 18th birthday tomorrow (17) -> not eligible',!$r['eligible']);
$r=$R::evaluate(base(s1a(['dob'=>dob_years_ago(86,1)]))); check('DOB: age 85, 86th birthday tomorrow -> may proceed',$r['eligible'],json_encode($r));
check('Age 85 -> licence data flag', isset($r['flags']['s1_age_85']));
$r=$R::evaluate(base(s1a(['dob'=>dob_years_ago(86)]))); check('DOB: 86th birthday today -> not eligible',!$r['eligible'] && strpos($r['reason'],'86')!==false,json_encode($r));
$r=$R::evaluate(base(s1a(['dob'=>dob_years_ago(90)]))); check('DOB: age 90 -> not eligible',!$r['eligible']);
$r=$R::evaluate(base(['dob'=>''])); check('Missing DOB -> not eligible',!$r['eligible']);
$r=$R::evaluate(base(['ageBand'=>'86-over'])); check('Age band 86-over -> not eligible',!$r['eligible']);
$r=$R::evaluate(base(['ageBand'=>'under-18'])); check('Age band under-18 -> not eligible',!$r['eligible']);
$r=$R::evaluate(base(['ageBand'=>'18-85'])); check('Age band 18-85 -> may proceed',$r['eligible']);
$r=$R::evaluate(base(['dob'=>dob_years_ago(74,-1)])); check('Age 74: no S1A questions needed, no S1A flag',$r['eligible'] && !isset($r['flags']['s1a']) && $r['prisma7_score']===null,json_encode($r));
$r=$R::evaluate(base(['dob'=>dob_years_ago(74,-1),'gpConsentShare'=>false])); check('Age 74, no GP consent -> red flag only',$r['eligible'] && isset($r['flags']['no_gp_consent']));
$d75=dob_years_ago(75);
$r=$R::evaluate(base(s1a(['dob'=>$d75]))); check('Age 75 (birthday today) with S1A answers -> may proceed, S1A flag',$r['eligible'] && isset($r['flags']['s1a']) && isset($r['flags']['triage_only']),json_encode($r));
check('Age 75: S1A flag carries the S1A heading', strpos($r['flags']['s1a'],'Age 75 to 85: rule S1A checks')===0);
$r=$R::evaluate(base(s1a(['dob'=>$d75,'gpConsentShare'=>false]))); check('Age 75, no GP consent -> red flag only, prescriber decides (GPhC 4.2 k)',$r['eligible'] && strpos($r['flags']['no_gp_consent'],'particular weight')!==false,json_encode($r));
$r=$R::evaluate(base(s1a(['dob'=>dob_years_ago(85),'gpConsentShare'=>false]))); check('Age 85, no GP consent -> red flag only',$r['eligible'] && isset($r['flags']['no_gp_consent']));
$r=$R::evaluate(base(['dob'=>$d75])); check('Age 75, S1A answers missing -> not eligible (unanswered)',!$r['eligible'] && strpos($r['reason'],'75')!==false);
$p=s1a(['dob'=>$d75]); unset($p['s1aPrisma']['walkingAid']); $r=$R::evaluate(base($p)); check('Age 75, a PRISMA-7 answer missing -> not eligible (unanswered)',!$r['eligible']);
$r=$R::evaluate(base(s1a(['dob'=>$d75,'s1aFalls'=>'maybe']))); check('Age 75, invalid falls answer -> not eligible (unanswered)',!$r['eligible']);
// S1A answers never auto-pass or auto-fail, except eGFR below 30 (E11).
$worst=s1a(['dob'=>$d75,'s1aFalls'=>'yes','s1aFracture'=>'yes','s1aMedsCount'=>'10-plus','s1aBpWater'=>'yes','s1aKidneyTest'=>'unsure','s1aEgfrResult'=>'','gpName'=>'','s1aPrisma'=>['limitActivities'=>'yes','needHelp'=>'yes','stayHome'=>'yes','countOnSomeone'=>'yes','walkingAid'=>'yes']]);
$r=$R::evaluate(base($worst)); check('S1A: every concerning answer -> still not screened out, all flagged',$r['eligible'] && isset($r['flags']['s1a_face_to_face'],$r['flags']['s1a_polypharmacy'],$r['flags']['s1a_bp_water'],$r['flags']['s1a_egfr'],$r['flags']['s1a_no_gp_name']),json_encode($r));
check('S1A: PRISMA-7 male + 5 yes = 6', $r['prisma7_score']===6, json_encode($r['prisma7_score']));
check('S1A: no recent kidney test -> GP blood test first flag', strpos($r['flags']['s1a_egfr'],'GP blood test')!==false);
$b=$R::evaluate(base(['weightKg'=>80,'heightCm'=>180]+s1a(['dob'=>$d75])));
check('S1A: best answers do not rescue a BMI fail (BMI 24.7)', !$b['eligible']);
$r=$R::evaluate(base(s1a(['dob'=>$d75]))); check('S1A: reassuring answers -> no face-to-face flag, PRISMA 1 (male)',$r['eligible'] && !isset($r['flags']['s1a_face_to_face']) && $r['prisma7_score']===1);
$r=$R::evaluate(base(s1a(['dob'=>$d75,'sex'=>'female','pregnant'=>'no','breastfeeding'=>'no','conceive'=>'no','couldConceive'=>'no','s1aPrisma'=>['limitActivities'=>'yes','needHelp'=>'yes','stayHome'=>'no','countOnSomeone'=>'no','walkingAid'=>'no']])));
check('S1A: female PRISMA-7 score 2 -> no face-to-face flag',$r['eligible'] && $r['prisma7_score']===2 && !isset($r['flags']['s1a_face_to_face']),json_encode($r));
$r=$R::evaluate(base(s1a(['dob'=>$d75,'s1aPrisma'=>['limitActivities'=>'yes','needHelp'=>'yes','stayHome'=>'no','countOnSomeone'=>'no','walkingAid'=>'no']])));
check('S1A: male PRISMA-7 score 3 -> face-to-face flag, still may proceed',$r['eligible'] && isset($r['flags']['s1a_face_to_face']));
$r=$R::evaluate(base(s1a(['dob'=>$d75,'s1aFalls'=>'yes']))); check('S1A: fall only -> face-to-face flag, may proceed',$r['eligible'] && isset($r['flags']['s1a_face_to_face']));
$r=$R::evaluate(base(s1a(['dob'=>$d75,'s1aEgfrResult'=>'29','s1aEgfrDate'=>'2026-05']))); check('S1A: eGFR 29 -> not eligible (E11 hard exclusion)',!$r['eligible']);
$r=$R::evaluate(base(s1a(['dob'=>$d75,'s1aEgfrResult'=>'30','s1aEgfrDate'=>'2026-05']))); check('S1A: eGFR 30 -> may proceed, result in flag',$r['eligible'] && strpos($r['flags']['s1a_egfr'],'eGFR 30')!==false,json_encode($r['flags']['s1a_egfr']??''));
$r=$R::evaluate(base(s1a(['dob'=>$d75,'s1aEgfrResult'=>'29.6','s1aEgfrDate'=>'2026-05']))); check('S1A: eGFR 29.6 -> not eligible (no rounding up to 30)',!$r['eligible']);
$r=$R::evaluate(base(s1a(['dob'=>$d75,'s1aEgfrResult'=>'29.95','s1aEgfrDate'=>'2026-05']))); check('S1A: eGFR 29.95 -> not eligible',!$r['eligible']);
$r=$R::evaluate(base(s1a(['dob'=>$d75,'s1aEgfrResult'=>'abc']))); check('S1A: non-numeric eGFR ignored, may proceed',$r['eligible']);
$r=$R::evaluate(base(['dob'=>dob_years_ago(60),'s1aEgfrResult'=>'20'])); check('Under 75: S1A fields ignored',$r['eligible'] && !isset($r['flags']['s1a']));
$rows=$R::s1a_summary(s1a(['sex'=>'male','s1aPrismaScore'=>1])); check('S1A summary lists answers and score', isset($rows['Falls in the last 12 months'],$rows['eGFR result'],$rows['PRISMA-7 Q7: Regularly uses a stick, walker or wheelchair']) && $rows['PRISMA-7 Q2: male']==='Yes' && isset($rows['PRISMA-7 score (yes answers; 3 or more: face-to-face)']), json_encode($rows));
check('S1A summary empty when not asked', $R::s1a_summary(base())===[]);
check('eGFR future month rejected', $R::egfr_date('2999-01')==='' && $R::egfr_date('2026-13')==='' && $R::egfr_date('2025-06')==='2025-06');

// ---- Orlistat, rules section O (WM-2026-10-v3) ----
function orl($o=[]){ return base(array_merge(['selectedTreatment'=>'orlistat','selectedDose'=>'120mg'],$o)); }
$fem=['sex'=>'female','pregnant'=>'no','breastfeeding'=>'no','conceive'=>'no'];
$bp=["I've been diagnosed with high blood pressure"];
// O2.2 BMI: 30, or 28 to 29.9 with a condition. 180 cm: 90.8 kg = 28.0, 93.96 = 29.0, 89.1 = 27.5
$r=$R::evaluate(orl(['weightKg'=>90.8,'heightCm'=>180,'weightConditions'=>$bp])); check('Orlistat: BMI 28.0 with condition -> may proceed',$r['eligible'] && isset($r['flags']['pathway']),json_encode($r));
$r=$R::evaluate(orl(['weightKg'=>90.8,'heightCm'=>180])); check('Orlistat: BMI 28.0 without condition -> not eligible',!$r['eligible'],json_encode($r));
$r=$R::evaluate(orl(['weightKg'=>93.96,'heightCm'=>180,'weightConditions'=>$bp])); check('Orlistat: BMI 29.0 with condition -> may proceed',$r['eligible']);
$r=$R::evaluate(orl(['weightKg'=>93.96,'heightCm'=>180])); check('Orlistat: BMI 29.0 without condition -> not eligible',!$r['eligible']);
$r=$R::evaluate(orl(['weightKg'=>89.1,'heightCm'=>180,'weightConditions'=>$bp])); check('Orlistat: BMI 27.5 with condition -> not eligible',!$r['eligible'] && strpos($r['reason'],'Orlistat')!==false,json_encode($r));
$r=$R::evaluate(base(['weightKg'=>89.1,'heightCm'=>180,'weightConditions'=>$bp,'selectedTreatment'=>'mounjaro'])); check('GLP-1: same BMI 27.5 with condition -> may proceed',$r['eligible']);
$r=$R::evaluate(orl(['weightKg'=>90.68,'heightCm'=>180,'weightConditions'=>$bp])); check('Orlistat: BMI 27.99 does not round up to 28',!$r['eligible']);
$r=$R::evaluate(orl(['weightKg'=>100,'heightCm'=>180])); check('Orlistat: BMI 30.9 -> may proceed, counselling flag, no GLP-1 pathway flag',$r['eligible'] && isset($r['flags']['orl_counselling']) && !isset($r['flags']['pathway']));
$r=$R::evaluate(orl(['weightKg'=>90.8,'heightCm'=>180,'diabetes'=>'type2-diet'])); check('Orlistat: BMI 28 with type 2 diabetes -> may proceed',$r['eligible']);
// Blocks OE1 to OE11
$r=$R::evaluate(orl(['dob'=>dob_years_ago(17)])); check('OE1: under 18 -> blocked',!$r['eligible']);
$r=$R::evaluate(orl(['allergiesList'=>'Allergic to Xenical capsules'])); check('OE2: allergy to orlistat -> blocked',!$r['eligible'] && strpos($r['reason'],'allergy')!==false);
$r=$R::evaluate(orl(['allergiesList'=>'Allium vegetables'])); check('OE2: no false "alli" allergy match',$r['eligible']);
$r=$R::evaluate(orl(array_merge($fem,['breastfeeding'=>'yes','couldConceive'=>'no']))); check('OE3: breastfeeding -> blocked',!$r['eligible']);
$r=$R::evaluate(orl(array_merge($fem,['pregnant'=>'yes','couldConceive'=>'yes']))); check('OE4: pregnant -> blocked',!$r['eligible']);
$r=$R::evaluate(orl(array_merge($fem,['conceive'=>'yes','couldConceive'=>'yes']))); check('OE4: planning pregnancy -> blocked',!$r['eligible']);
$r=$R::evaluate(orl(['sex'=>'female'])); check('Orlistat: pregnancy questions still required',!$r['eligible']);
$r=$R::evaluate(orl(['conditions'=>['I have chronic malabsorption syndrome']])); check('OE5: malabsorption -> blocked',!$r['eligible']);
$r=$R::evaluate(orl(['conditions'=>['I have cholestasis']])); check('OE6: cholestasis -> blocked',!$r['eligible']);
$r=$R::evaluate(orl(['currentMedsList'=>'Wegovy 1mg weekly, ramipril'])); check('OE7: new patient taking a GLP-1 alongside -> blocked',!$r['eligible'] && strpos($r['reason'],'alongside')!==false,json_encode($r));
$r=$R::evaluate(orl(['currentMedsList'=>'Ozempic'])); check('OE7: diabetes GLP-1 (Ozempic) alongside -> blocked',!$r['eligible']);
$r=$R::evaluate(orl(['currentMedsList'=>'phentermine'])); check('OE7: other weight-loss medicine -> blocked',!$r['eligible']);
$r=$R::evaluate(orl(['currentMedsList'=>'alli 60mg'])); check('OE7: alli or orlistat elsewhere -> flagged, not blocked',$r['eligible'] && isset($r['flags']['orl_existing_orlistat']));
$r=$R::evaluate(orl(['conditions'=>['I have or have had an eating disorder']])); check('OE9: eating disorder -> blocked',!$r['eligible']);
$r=$R::evaluate(orl(['gpConsentSCR'=>false])); check('OE10: refuses SCR check -> blocked',!$r['eligible']);
$r=$R::evaluate(orl(['consentIdVideo'=>false])); check('OE10: refuses ID and weight check -> blocked',!$r['eligible']);
$r=$R::evaluate(orl(['conditions'=>['I have had a bariatric operation'],'bariatricRecent'=>'yes'])); check('OE11: bariatric surgery within 6 months -> blocked',!$r['eligible']);
check('OE8: reorder BMI 19.9 blocked by the shared floor (constant)', $R::ORLISTAT_TRANSFER_FLOOR===20);
// Red flags OF1 to OF15, each from the free-text list, each with a basis
$of=['OF1'=>['ciclosporin','orl_of1_ciclosporin'],'OF2'=>['acarbose','orl_of2_acarbose'],'OF3'=>['amiodarone','orl_of3_amiodarone'],'OF4'=>['apixaban 5mg','orl_of4_anticoagulant'],'OF5'=>['levothyroxine 100mcg','orl_of5_thyroid'],'OF6'=>['lamotrigine','orl_of6_antiepileptic'],'OF7'=>['Biktarvy','orl_of7_hiv'],'OF8'=>['sertraline 50mg','orl_of8_psychiatric'],'OF9'=>['metformin','orl_of9_diabetes'],'OF10'=>['atorvastatin 20mg','orl_of10_bp'],'OF11'=>['furosemide','orl_of11_kidney'],'OF15'=>['Microgynon','orl_of15_pill']];
foreach($of as $code=>$c){ $r=$R::evaluate(orl(['currentMedsList'=>$c[0]])); check("$code: {$c[0]} -> may proceed with red flag and basis",$r['eligible'] && isset($r['flags'][$c[1]]) && strpos($r['flags'][$c[1]],$code)!==false && strpos($r['flags'][$c[1]],'Basis:')!==false,json_encode(array_keys($r['flags']))); }
$r=$R::evaluate(orl(['currentMedsList'=>'amlodipine'])); check('OF10: blood pressure medicine flagged',isset($r['flags']['orl_of10_bp']));
$r=$R::evaluate(orl(['otherConditionsList'=>'Stage 3 chronic kidney disease'])); check('OF11: kidney disease from free-text conditions',$r['eligible'] && isset($r['flags']['orl_of11_kidney']));
$r=$R::evaluate(orl(['otherConditionsList'=>'hepatitis B'])); check('OF12: liver disease from free-text conditions',$r['eligible'] && isset($r['flags']['orl_of12_liver']));
$r=$R::evaluate(orl(['weightConditions'=>['I have fatty liver disease']])); check('OF12: fatty liver flagged',isset($r['flags']['orl_of12_liver']));
$r=$R::evaluate(orl(['conditions'=>['I have had a bariatric operation'],'bariatricRecent'=>'no'])); check('OF13: bariatric over 6 months -> flag, malabsorption check',$r['eligible'] && strpos($r['flags']['orl_of13_bariatric'],'OE5')!==false);
$r=$R::evaluate(orl(s1a(['dob'=>dob_years_ago(76)]))); check('OF14: age 76 -> flag with rule O2.1 wording',$r['eligible'] && strpos($r['flags']['orl_of14_age'],'Outside the Deltera PGD age range (18 to 75); not studied in the elderly; independent prescriber decision, record reasons.')!==false,json_encode($r['flags']['orl_of14_age']??''));
$r=$R::evaluate(orl(['dob'=>dob_years_ago(60)])); check('OF14: age 60 -> no age flag',!isset($r['flags']['orl_of14_age']));
$r=$R::evaluate(orl(s1a(['dob'=>dob_years_ago(86)]))); check('Orlistat: 86 -> blocked (rule O2.1)',!$r['eligible']);
$r=$R::evaluate(orl(array_merge($fem,['couldConceive'=>'yes','consentContraception'=>false,'contraception'=>'pill']))); check('Orlistat: no contraception agreement needed; pill -> OF15',$r['eligible'] && isset($r['flags']['orl_of15_pill']),json_encode($r));
$r=$R::evaluate(orl(array_merge($fem,['couldConceive'=>'yes','consentContraception'=>false,'contraception'=>'none']))); check('Orlistat: no contraception, no agreement -> may proceed, counselling flag',$r['eligible'] && isset($r['flags']['orl_contraception_none']));
$r=$R::evaluate(base(array_merge($fem,['couldConceive'=>'yes','consentContraception'=>false,'contraception'=>'pill','selectedTreatment'=>'mounjaro']))); check('GLP-1: contraception agreement still required',!$r['eligible']);
// OF16: GLP-1-only exclusions are information flags for Orlistat
$r=$R::evaluate(orl(['diabetes'=>'type1'])); check('OF16: type 1 diabetes -> information flag, not blocked',$r['eligible'] && strpos($r['flags']['orl_of16_info'],'type 1 diabetes')!==false);
foreach (['I have severe kidney disease or kidney failure','I have severe stomach or bowel problems, including gastroparesis (very slow stomach emptying)','My weight gain is caused by a hormone condition or by a medicine I take','I have a history of pancreatitis',"I have a family history of thyroid cancer and/or I've had thyroid cancer",'I have diabetic retinopathy','I have severe heart failure',"I'm currently being treated for cancer",'I have severe liver disease (for example cirrhosis)','I have Multiple endocrine neoplasia type 2 (MEN2)','I have had surgery or an operation to my thyroid'] as $c){
  $r=$R::evaluate(orl(['conditions'=>[$c]])); check('OF16 info for Orlistat: '.substr($c,0,38),$r['eligible'] && isset($r['flags']['orl_of16_info']),json_encode($r));
  $g=$R::evaluate(base(['conditions'=>[$c],'selectedTreatment'=>'wegovy'])); check('  still a GLP-1 block: '.substr($c,0,38),!$g['eligible']);
}
$r=$R::evaluate(orl(['conditions'=>['I have severe kidney disease or kidney failure']])); check('OF16 kidney also raises OF11',isset($r['flags']['orl_of11_kidney_condition']));
$r=$R::evaluate(orl(s1a(['dob'=>$d75,'s1aEgfrResult'=>'25','s1aEgfrDate'=>'2026-05']))); check('Orlistat: eGFR 25 at 75 -> OF11/OF16 flag, not blocked (GLP-1 E11 only)',$r['eligible'] && isset($r['flags']['orl_of11_egfr'],$r['flags']['orl_of16_info']),json_encode($r));
$r=$R::evaluate(orl(['currentMedsList'=>'gliclazide'])); check('Orlistat: GLP-1 medicine notes labelled GLP-1 only',strpos($r['flags']['med_sulfonylurea'],'GLP-1 products only')===0 && isset($r['flags']['orl_of9_diabetes']));
// O2.3 / O2.4: no ladder, no switching table; moves are new starts
$ld=(new DateTime('-5 days'))->format('Y-m-d');
$r=$R::evaluate(orl(['userType'=>'switching','currentMedication'=>'mounjaro','currentDose'=>'7.5mg','lastDoseDate'=>$ld,'weightKg'=>84,'heightCm'=>180,'startWeightKg'=>110,'currentMedsList'=>'Mounjaro'])); check('GLP-1 to Orlistat: BMI 25.9 -> new start rules, not eligible',!$r['eligible']);
$r=$R::evaluate(orl(['userType'=>'switching','currentMedication'=>'mounjaro','currentDose'=>'7.5mg','lastDoseDate'=>$ld,'weightKg'=>100,'heightCm'=>180,'startWeightKg'=>110,'currentMedsList'=>'Mounjaro'])); check('GLP-1 to Orlistat: BMI 30.9 -> new start, OE7 confirm-stopped flag',$r['eligible'] && $r['pathway']==='new' && isset($r['flags']['orl_new_start'],$r['flags']['orl_oe7_confirm_stopped']) && !isset($r['flags']['proof_required']),json_encode($r));
$old=(new DateTime('-200 days'))->format('Y-m-d');
$r=$R::evaluate(orl(['userType'=>'switching','currentMedication'=>'wegovy','lastDoseDate'=>$old,'weightKg'=>100,'heightCm'=>180])); check('GLP-1 to Orlistat: no 3-month restart logic',$r['eligible'] && !isset($r['flags']['restart_over_3m']));
$r=$R::evaluate(base(['userType'=>'switching','currentMedication'=>'orlistat','currentDose'=>'120mg','lastDoseDate'=>$ld,'weightKg'=>84,'heightCm'=>180,'startWeightKg'=>100,'selectedTreatment'=>'mounjaro'])); check('Orlistat to GLP-1: BMI 25.9 -> new start on GLP-1, not eligible',!$r['eligible']);
$r=$R::evaluate(base(['userType'=>'switching','currentMedication'=>'orlistat','currentDose'=>'120mg','lastDoseDate'=>$ld,'weightKg'=>100,'heightCm'=>180,'startWeightKg'=>110,'selectedTreatment'=>'mounjaro'])); check('Orlistat to GLP-1: BMI 30.9 -> new start flag',$r['eligible'] && $r['pathway']==='new' && isset($r['flags']['from_orlistat']) && !isset($r['flags']['proof_required']));
$r=$R::evaluate(orl(['userType'=>'switching','currentMedication'=>'orlistat','currentDose'=>'120mg','lastDoseDate'=>$ld,'weightKg'=>70,'heightCm'=>180,'startWeightKg'=>100])); check('Orlistat transfer: start 30.9, now 21.6 -> transfer with proof and 12-week evidence flags',$r['eligible'] && $r['pathway']==='transfer' && isset($r['flags']['proof_required'],$r['flags']['orl_transfer_12_week']),json_encode($r));
$r=$R::evaluate(orl(['userType'=>'switching','currentMedication'=>'orlistat','currentDose'=>'120mg','lastDoseDate'=>$ld,'weightKg'=>98,'heightCm'=>180,'startWeightKg'=>100])); check('Orlistat transfer: declared loss 2% -> flagged under 5%',$r['eligible'] && strpos($r['flags']['orl_transfer_12_week'],'under 5%')!==false);
$r=$R::evaluate(orl(['userType'=>'switching','currentMedication'=>'orlistat','currentDose'=>'120mg','lastDoseDate'=>$ld,'weightKg'=>64,'heightCm'=>180,'startWeightKg'=>100])); check('Orlistat transfer: now BMI 19.8 -> not eligible',!$r['eligible']);
$r=$R::evaluate(orl(['userType'=>'switching','currentMedication'=>'orlistat','currentDose'=>'120mg','lastDoseDate'=>$ld,'weightKg'=>100,'heightCm'=>180,'startWeightKg'=>85])); check('Orlistat transfer: start BMI 26.2 but now 30.9 -> assessed as new start',$r['eligible'] && $r['pathway']==='new' && isset($r['flags']['orl_transfer_as_new']));
$r=$R::evaluate(orl(['userType'=>'switching','currentMedication'=>'orlistat','lastDoseDate'=>'','weightKg'=>100,'heightCm'=>180,'startWeightKg'=>110])); check('Orlistat transfer: last dose date still required',!$r['eligible']);
$x=$D::propose_start_dose('mounjaro','7.5mg','orlistat',3); check('Dose: GLP-1 to Orlistat -> 120mg, Orlistat rule', $x['dose']==='120mg' && $x['rule']==='orlistat_single_strength');
$x=$D::propose_start_dose('orlistat','120mg','wegovy',3); check('Dose: Orlistat to Wegovy -> first step 0.25mg', $x['dose']==='0.25mg' && $x['rule']==='from_orlistat_new_start');
check('Dose: Orlistat ladder is one strength', $D::ladder('orlistat')===['120mg']);
// Reorders: rule O4.2 (12 weeks from first supply), O4.3 (BMI bands)
$RR='TC_Reorder_Rules';
$w=$RR::orlistat_twelve_week(100,97,40); check('Reorder at day 40: 12-week rule not yet, weights carried',!$w['block'] && $w['applies']===false && isset($w['flags']['orl_weight_change']));
$w=$RR::orlistat_twelve_week(100,97,57); check('Reorder at day 57 (supply runs past 12 weeks), 3% loss -> blocked',$w['block'] && strpos($w['flags']['orl_12_week_stop'],'Under 5% at 12 weeks: stop under the licence')!==false,json_encode($w));
$w=$RR::orlistat_twelve_week(100,95,90); check('Reorder at day 90, 5% loss -> not blocked, verify flag',!$w['block'] && isset($w['flags']['orl_12_week_ok']));
$w=$RR::orlistat_twelve_week(100,96,120); check('Reorder at day 120, 4% loss -> blocked (every later reorder)',$w['block']);
$w=$RR::orlistat_twelve_week(100,101,90); check('Weight gain at 12 weeks -> blocked, shows +1.0%',$w['block'] && strpos($w['flags']['orl_12_week_stop'],'+1.0%')!==false);
$w=$RR::orlistat_twelve_week(null,90,90); check('Start weight unknown -> red flag, no silent pass',!$w['block'] && isset($w['flags']['orl_12_week_unknown']));
$w=$RR::orlistat_twelve_week(100,90,null); check('First supply date unknown -> red flag',isset($w['flags']['orl_12_week_unknown']));
check('Reorder BMI 21 -> 20 to 22.9 flag (Orlistat O4.3)', isset($RR::bmi_band_flags(21.0,'orlistat')['bmi_20_23']) && strpos($RR::bmi_band_flags(21.0,'orlistat')['bmi_20_23'],'O4.3')!==false);
check('Reorder BMI 22.9 flagged, 23.0 and 19.9 not (floor blocks separately)', $RR::bmi_band_flags(22.9,'mounjaro') && !$RR::bmi_band_flags(23.0,'mounjaro') && !$RR::bmi_band_flags(19.9,'orlistat'));
$f=$RR::orlistat_new_medicine_flags('started warfarin and Wegovy'); check('Reorder new medicines: OE7 and OF4 flagged',isset($f['orl_oe7_reorder'],$f['orl_of4_anticoagulant']),json_encode(array_keys($f)));
check('Percent loss maths', abs($R::percent_loss(100,95)-5.0)<1e-9 && $R::percent_loss(0,90)===null);
echo "\n$pass passed, $fail failed\n"; exit($fail?1:0);
