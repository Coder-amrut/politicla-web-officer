<?php

namespace Database\Seeders;

use App\Models\SurveyForm;
use Illuminate\Database\Seeder;

class SurveyFormSeeder extends Seeder
{
    public static function getDefaultTemplates(): array
    {
        return [
            'DISTRICT' => [
                'formTitle' => ['en' => 'District & Zilla Parishad Governance Survey', 'mr' => 'जिल्हा परिषद व स्थानिक विकास पाहणी'],
                'campaignObjective' => 'civic_governance',
                'targetAudience' => 'all_residents',
                'modules' => [
                    'location' => true,
                    'electoral' => true,
                    'demographics' => true,
                    'personalInfo' => true,
                    'educationOccupation' => true,
                    'voterElectoral' => true,
                    'scopeSpecialized' => true,
                    'issuesPriorities' => true,
                    'publicServices' => true,
                    'communicationOutreach' => true,
                    'volunteerEnrollment' => true,
                    'openFeedback' => true,
                    'consent' => true,
                ],
                'specializedTitle' => ['en' => '🏛️ District & Zilla Parishad Governance Evaluation', 'mr' => '🏛️ जिल्हा परिषद व तालुका प्रशासन मूल्यमापन'],
                'specializedQuestions' => [
                    [
                        'id' => 'zp_member_rating',
                        'label' => ['en' => 'Sitting Zilla Parishad Member Accessibility & Work', 'mr' => 'विद्यमान जिल्हा परिषद सदस्य संपर्क व विकासकामे'],
                        'type' => 'rating',
                        'required' => true,
                    ],
                    [
                        'id' => 'phc_health_service',
                        'label' => ['en' => 'Primary Health Center (PHC) & Doctor Availability', 'mr' => 'प्राथमिक आरोग्य केंद्र (PHC) औषधे व डॉक्टर उपलब्धता'],
                        'type' => 'single_choice',
                        'required' => true,
                        'options' => [
                            ['label' => ['en' => 'Good / Regular Doctor & Medicines', 'mr' => 'उत्तम / नियमित डॉक्टर व औषध पुरवठा']],
                            ['label' => ['en' => 'Average / Frequent Medicine Shortage', 'mr' => 'साधारण / औषध टंचाई जाणवते']],
                            ['label' => ['en' => 'Poor / Doctor Frequently Absent', 'mr' => 'असमाधानकारक / डॉक्टर सहसा नसतात']],
                        ],
                    ],
                    [
                        'id' => 'jal_jeevan_tap',
                        'label' => ['en' => 'Jal Jeevan Mission Tap Water in Village/Ghar', 'mr' => 'जलजीवन मिशन नळ पाणीपुरवठा स्थिती'],
                        'type' => 'single_choice',
                        'required' => true,
                        'options' => [
                            ['label' => ['en' => 'Tap Connected & Regular Water', 'mr' => 'नळ जोडणी पूर्ण व पाणी नियमित']],
                            ['label' => ['en' => 'Tap Fitted but Water Irregular', 'mr' => 'नळ आहे पण पाणी अनियमित']],
                            ['label' => ['en' => 'Work Incomplete / No Tap Water', 'mr' => 'काम अपूर्ण / नळाचे पाणी नाही']],
                        ],
                    ],
                    [
                        'id' => 'tehsil_satbara',
                        'label' => ['en' => 'Tehsil & Talathi Office Services (7/12, Ferfar, Land)', 'mr' => 'तहसील व तलाठी कार्यालय कामे (७/१२, फेरफार नोंद)'],
                        'type' => 'single_choice',
                        'required' => false,
                        'options' => [
                            ['label' => ['en' => 'Smooth & Done on Time', 'mr' => 'सहज व विनाअडथळा वेळेत काम होते']],
                            ['label' => ['en' => 'Frequent Delays & Roundabouts', 'mr' => 'वारंवार हेलपाटे मारावे लागतात']],
                            ['label' => ['en' => 'Pending for Months without Action', 'mr' => 'महिने उलटूनही काम प्रलंबित राहते']],
                        ],
                    ],
                ],
                'issues' => [
                    ['id' => 'roads', 'label' => ['en' => 'District & Rural Roads (PMGSY)', 'mr' => 'ग्रामीण व जिल्हा रस्ते दुरुस्ती', 'hi' => 'सड़कें'], 'icon' => '🛣️'],
                    ['id' => 'water', 'label' => ['en' => 'Drinking Water & Jal Jeevan Mission', 'mr' => 'पिण्याचे पाणी व जलजीवन मिशन', 'hi' => 'पेयजल'], 'icon' => '💧'],
                    ['id' => 'healthcare', 'label' => ['en' => 'PHC / Rural Hospitals & Doctors', 'mr' => 'प्राथमिक आरोग्य केंद्र व ग्रामीण रुग्णालय', 'hi' => 'स्वास्थ्य केंद्र'], 'icon' => '🏥'],
                    ['id' => 'education', 'label' => ['en' => 'ZP Primary Schools Infrastructure', 'mr' => 'जि. प. प्राथमिक शाळा व शिक्षक उपस्थिती', 'hi' => 'प्राथमिक स्कूल'], 'icon' => '🏫'],
                    ['id' => 'agri_power', 'label' => ['en' => 'Agriculture Electricity & Transformer Repair', 'mr' => 'शेती वीज पुरवठा व जळालेले रोहित्र (DP) बदलणे', 'hi' => 'कृषि बिजली'], 'icon' => '⚡'],
                    ['id' => 'land_revenue', 'label' => ['en' => 'Revenue Department & Satbara Services', 'mr' => 'महसूल विभाग, फेरफार व ७/१२ दाखले', 'hi' => 'राजस्व सेवाएं'], 'icon' => '📄'],
                ],
                'customQuestions' => [],
            ],

            'LOK_SABHA' => [
                'formTitle' => ['en' => 'Lok Sabha Parliamentary Election Survey', 'mr' => 'लोकसभा मतदारसंघ निवडणूक व खासदार मूल्यमापन पाहणी'],
                'campaignObjective' => 'parliamentary_campaign',
                'targetAudience' => 'all_voters',
                'modules' => [
                    'location' => true,
                    'electoral' => true,
                    'demographics' => true,
                    'personalInfo' => true,
                    'educationOccupation' => true,
                    'voterElectoral' => true,
                    'scopeSpecialized' => true,
                    'issuesPriorities' => true,
                    'publicServices' => true,
                    'communicationOutreach' => true,
                    'volunteerEnrollment' => true,
                    'openFeedback' => true,
                    'consent' => true,
                ],
                'specializedTitle' => ['en' => '🇮🇳 Lok Sabha MP & Central Governance Evaluation', 'mr' => '🇮🇳 खासदार कामगिरी व राष्ट्रीय मुद्दे मूल्यमापन'],
                'specializedQuestions' => [
                    [
                        'id' => 'mp_rating',
                        'label' => ['en' => 'Sitting Member of Parliament (MP) Performance', 'mr' => 'विद्यमान खासदारांची कामगिरी व मतदारसंघ विकास'],
                        'type' => 'rating',
                        'required' => true,
                    ],
                    [
                        'id' => 'pm_kisan_benefit',
                        'label' => ['en' => 'PM Kisan Samman Nidhi (₹6,000/yr) Benefit', 'mr' => 'पीएम किसान सन्मान निधी (₹६०००/वर्ष) लाभ मिळतो का?'],
                        'type' => 'single_choice',
                        'required' => true,
                        'options' => [
                            ['label' => ['en' => 'Receiving Regularly in Bank Account', 'mr' => 'नियमित हप्ते बँक खात्यात मिळतात']],
                            ['label' => ['en' => 'Applied / Awaiting e-KYC or Land Record Sync', 'mr' => 'अर्ज केलाय / e-KYC किंवा पडताळणी प्रलंबित']],
                            ['label' => ['en' => 'Not Eligible / Landless Farmer', 'mr' => 'पात्र नाही / शेतजमीन नाही']],
                        ],
                    ],
                    [
                        'id' => 'ayushman_card',
                        'label' => ['en' => 'Ayushman Bharat Card (₹5 Lakh Free Treatment)', 'mr' => 'आयुष्मान भारत कार्ड (₹५ लाख मोफत उपचार)'],
                        'type' => 'single_choice',
                        'required' => false,
                        'options' => [
                            ['label' => ['en' => 'Have Card & Received Free Treatment', 'mr' => 'कार्ड आहे व उपचाराचा लाभ घेतला']],
                            ['label' => ['en' => 'Have Card, Never Needed to Use', 'mr' => 'कार्ड आहे पण वापरण्याची वेळ आली नाही']],
                            ['label' => ['en' => 'Do Not Have Card / Want to Apply', 'mr' => 'कार्ड नाही / काढायचे आहे']],
                        ],
                    ],
                    [
                        'id' => 'pm_awas',
                        'label' => ['en' => 'PM Awas Yojana (Pucca House Grant)', 'mr' => 'प्रधानमंत्री आवास योजना (घरकुल)'],
                        'type' => 'single_choice',
                        'required' => false,
                        'options' => [
                            ['label' => ['en' => 'Received Pucca House Benefit', 'mr' => 'घरकुल मंजूर झाले व लाभ मिळाला']],
                            ['label' => ['en' => 'In Waiting List / Applied', 'mr' => 'प्रतीक्षा यादीत नाव आहे / अर्ज भरलाय']],
                            ['label' => ['en' => 'Kaccha House / Not Received Yet', 'mr' => 'कच्चे घर आहे / अद्याप लाभ नाही']],
                        ],
                    ],
                    [
                        'id' => 'voting_driver',
                        'label' => ['en' => 'Primary Deciding Factor for Lok Sabha Vote', 'mr' => 'लोकसभा निवडणुकीत मत देताना मुख्य निकष कोणता?'],
                        'type' => 'single_choice',
                        'required' => true,
                        'options' => [
                            ['label' => ['en' => 'Prime Minister / National Leadership', 'mr' => 'राष्ट्रीय नेतृत्व / पंतप्रधान']],
                            ['label' => ['en' => 'Local MP Candidate Work & Accessibility', 'mr' => 'स्थानिक खासदार उमेदवाराची कामे व जनसंपर्क']],
                            ['label' => ['en' => 'Party & Political Ideology', 'mr' => 'राजकीय पक्ष व विचारसरणी']],
                            ['label' => ['en' => 'Control on Inflation & Youth Employment', 'mr' => 'महागाई नियंत्रण व तरुणांना रोजगार']],
                        ],
                    ],
                ],
                'issues' => [
                    ['id' => 'inflation', 'label' => ['en' => 'Inflation & Daily Household Expenses', 'mr' => 'महागाई व वाढता कौटुंबिक खर्च', 'hi' => 'महंगाई'], 'icon' => '📈'],
                    ['id' => 'employment', 'label' => ['en' => 'Youth Employment & Industry Jobs', 'mr' => 'तरुणांना रोजगार व नोकरीच्या संधी', 'hi' => 'रोजगार'], 'icon' => '💼'],
                    ['id' => 'railways', 'label' => ['en' => 'Railway Trains, Stops & Connectivity', 'mr' => 'रेल्वे गाड्यांचे थांबे व नवीन मार्ग', 'hi' => 'रेलवे'], 'icon' => '🚆'],
                    ['id' => 'highways', 'label' => ['en' => 'National Highway Infrastructure & Tolls', 'mr' => 'राष्ट्रीय महामार्ग, बायपास व टोल दर', 'hi' => 'हाईवे'], 'icon' => '🛣️'],
                    ['id' => 'crop_msp', 'label' => ['en' => 'Farm MSP & Agriculture Export Policy', 'mr' => 'शेतमालाला हमीभाव व निर्यात धोरण', 'hi' => 'फसल एमएसपी'], 'icon' => '🌾'],
                    ['id' => 'national_safety', 'label' => ['en' => 'National Security & Border Defense', 'mr' => 'राष्ट्रीय सुरक्षा व संरक्षण', 'hi' => 'राष्ट्रीय सुरक्षा'], 'icon' => '🛡️'],
                ],
                'customQuestions' => [],
            ],

            'ASSEMBLY' => [
                'formTitle' => ['en' => 'Maharashtra Vidhan Sabha Assembly Election Survey', 'mr' => 'महाराष्ट्र विधानसभा निवडणूक व आमदार मूल्यमापन पाहणी'],
                'campaignObjective' => 'assembly_campaign',
                'targetAudience' => 'all_voters',
                'modules' => [
                    'location' => true,
                    'electoral' => true,
                    'demographics' => true,
                    'personalInfo' => true,
                    'educationOccupation' => true,
                    'voterElectoral' => true,
                    'scopeSpecialized' => true,
                    'issuesPriorities' => true,
                    'publicServices' => true,
                    'communicationOutreach' => true,
                    'volunteerEnrollment' => true,
                    'openFeedback' => true,
                    'consent' => true,
                ],
                'specializedTitle' => ['en' => '🏛️ MLA Performance & Maharashtra State Schemes Evaluation', 'mr' => '🏛️ आमदार कामगिरी व महाराष्ट्र राज्य कल्याणकारी योजना'],
                'specializedQuestions' => [
                    [
                        'id' => 'mla_rating',
                        'label' => ['en' => 'Sitting MLA (आमदार) Performance & Accessibility', 'mr' => 'विद्यमान आमदारांची कामगिरी व जनतेमधील संपर्क'],
                        'type' => 'rating',
                        'required' => true,
                    ],
                    [
                        'id' => 'ladki_bahin',
                        'label' => ['en' => 'Mukhyamantri Majhi Ladki Bahin Yojana (₹1,500/mo)', 'mr' => 'मुख्यमंत्री माझी लाडकी बहीण योजना (₹१,५००/महिना)'],
                        'type' => 'single_choice',
                        'required' => true,
                        'options' => [
                            ['label' => ['en' => 'Receiving ₹1,500 Regularly in Bank Account', 'mr' => 'नियमित ₹१५०० बँक खात्यात मिळतात']],
                            ['label' => ['en' => 'Applied, Approval / DBT Verification Pending', 'mr' => 'अर्ज भरलाय, DBT/मंजुरी प्रलंबित आहे']],
                            ['label' => ['en' => 'Form Rejected / Technical Glitch', 'mr' => 'अर्ज बाद झाला / तांत्रिक अडचण']],
                            ['label' => ['en' => 'Not Eligible / Did Not Apply', 'mr' => 'पात्र नाही / अर्ज भरलेला नाही']],
                        ],
                    ],
                    [
                        'id' => 'namo_shetkari',
                        'label' => ['en' => 'Namo Shetkari Mahasanman Nidhi (State ₹6,000)', 'mr' => 'नमो शेतकरी महासन्मान निधी (राज्य सरकार ₹६०००)'],
                        'type' => 'single_choice',
                        'required' => false,
                        'options' => [
                            ['label' => ['en' => 'Receiving Regularly with PM Kisan', 'mr' => 'नियमित हप्ते बँक खात्यात मिळतात']],
                            ['label' => ['en' => 'Applied / Verification in Process', 'mr' => 'अर्ज प्रक्रिया चालू आहे']],
                            ['label' => ['en' => 'Not Received / Ineligible', 'mr' => 'हप्ता मिळालेला नाही / अपात्र']],
                        ],
                    ],
                    [
                        'id' => 'free_agri_power',
                        'label' => ['en' => 'Free Electricity for Agricultural Pumps (कृषी पंप वीज)', 'mr' => 'शेतकऱ्यांना मोफत कृषी पंप वीज सवलत'],
                        'type' => 'single_choice',
                        'required' => false,
                        'options' => [
                            ['label' => ['en' => 'Benefited / Zero Bill Received', 'mr' => 'लाभ मिळाला / शून्य वीज बिल आले']],
                            ['label' => ['en' => 'Still Receiving Regular Power Bills', 'mr' => 'अजूनही नियमित वीज बिल येते']],
                            ['label' => ['en' => 'Low Voltage / Burning Transformer Problems', 'mr' => 'कमी व्होल्टेज व रोहित्र/DP समस्या']],
                        ],
                    ],
                    [
                        'id' => 'bus_travel_50',
                        'label' => ['en' => '50% ST Bus Travel Concession for Women', 'mr' => 'महिलांना एसटी बस प्रवासात ५०% सवलत'],
                        'type' => 'single_choice',
                        'required' => false,
                        'options' => [
                            ['label' => ['en' => 'Availing Regularly / Great Relief', 'mr' => 'नेहमी लाभ घेतात / मोठा दिलासा']],
                            ['label' => ['en' => 'Rarely Use ST Bus', 'mr' => 'कधीतरी एसटी बसने प्रवास करतात']],
                            ['label' => ['en' => 'Bus Scarcity / Buses not stopping in Village', 'mr' => 'गावात बस फेऱ्यांची कमतरता']],
                        ],
                    ],
                    [
                        'id' => 'pik_vima',
                        'label' => ['en' => '₹1 Crop Insurance (एक रुपयात पीक विमा) Compensation', 'mr' => '₹१ पीक विमा नुकसान भरपाई मिळाली का?'],
                        'type' => 'single_choice',
                        'required' => false,
                        'options' => [
                            ['label' => ['en' => 'Compensation Received in Full', 'mr' => 'नुकसान भरपाई खात्यात जमा झाली']],
                            ['label' => ['en' => 'Claim Submitted / Survey in Progress', 'mr' => 'दाखल केलाय / पंचनामा सर्वेक्षण बाकी']],
                            ['label' => ['en' => 'Claim Rejected / Zero Compensation', 'mr' => 'अपात्र ठरवले / भरपाई मिळाली नाही']],
                        ],
                    ],
                ],
                'issues' => [
                    ['id' => 'irrigation', 'label' => ['en' => 'Canal Water & Irrigation Schemes', 'mr' => 'शेततळी, कालवे व शेती सिंचन पाणी', 'hi' => 'सिंचाई व नहरें'], 'icon' => '🌾'],
                    ['id' => 'midc_jobs', 'label' => ['en' => 'Local MIDC & Industrial Jobs for Youth', 'mr' => 'स्थानिक MIDC मध्ये स्थानिक तरुणांना नोकऱ्या', 'hi' => 'स्थानीय उद्योग'], 'icon' => '🏭'],
                    ['id' => 'roads', 'label' => ['en' => 'Constituency Roads & Pothole Repair', 'mr' => 'मतदारसंघातील मुख्य रस्ते व खड्डे दुरुस्ती', 'hi' => 'सड़कें'], 'icon' => '🛣️'],
                    ['id' => 'water', 'label' => ['en' => 'Drinking Water Tap Connections', 'mr' => 'पिण्याचे शुद्ध पाणी व नळ योजना', 'hi' => 'पेयजल'], 'icon' => '💧'],
                    ['id' => 'subdistrict_hospital', 'label' => ['en' => 'Sub-District Hospital & ICU Facilities', 'mr' => 'उपजिल्हा रुग्णालय, औषधे व तज्ज्ञ डॉक्टर', 'hi' => 'अस्पताल'], 'icon' => '🏥'],
                    ['id' => 'crop_msp', 'label' => ['en' => 'Crop MSP & Agricultural Market Subsidies', 'mr' => 'सोयाबीन, कापूस, कांदा हमीभाव व अनुदान', 'hi' => 'फसल मूल्य'], 'icon' => '🚜'],
                ],
                'customQuestions' => [],
            ],

            'POLLING_STATION' => [
                'formTitle' => ['en' => 'Booth Level Micro-Survey & Panna Management', 'mr' => 'मतदान केंद्र (बूथ) व गल्ली पातळी सूक्ष्म पाहणी'],
                'campaignObjective' => 'booth_management',
                'targetAudience' => 'booth_residents',
                'modules' => [
                    'location' => true,
                    'electoral' => true,
                    'demographics' => true,
                    'personalInfo' => true,
                    'educationOccupation' => true,
                    'voterElectoral' => true,
                    'scopeSpecialized' => true,
                    'issuesPriorities' => true,
                    'publicServices' => true,
                    'communicationOutreach' => true,
                    'volunteerEnrollment' => true,
                    'openFeedback' => true,
                    'consent' => true,
                ],
                'specializedTitle' => ['en' => '▦ Hyper-Local Booth & Ward Civic Infrastructure', 'mr' => '▦ बूथ / प्रभाग पातळीवरील मूलभूत नागरी सुविधा'],
                'specializedQuestions' => [
                    [
                        'id' => 'tap_water_supply',
                        'label' => ['en' => 'Tap Water Regularity & Pressure in Galli/Lane', 'mr' => 'गल्लीतील नळाचे पाणी किती दिवसांनी व कसे येते?'],
                        'type' => 'single_choice',
                        'required' => true,
                        'options' => [
                            ['label' => ['en' => 'Daily with Sufficient Pressure', 'mr' => 'दररोज पुरेसे व चांगल्या दाबाने']],
                            ['label' => ['en' => 'Alternate Days (एक दिवसाआड)', 'mr' => 'एक दिवसाआड पाणी येते']],
                            ['label' => ['en' => 'Twice a Week / Irregular', 'mr' => 'आठवड्यातून २-३ दिवस / अनियमित']],
                            ['label' => ['en' => 'Dependent on Private Water Tankers', 'mr' => 'पाण्यासाठी टँकरवर अवलंबून राहावे लागते']],
                        ],
                    ],
                    [
                        'id' => 'drainage_gutter',
                        'label' => ['en' => 'Drainage & Open Gutter Cleanliness', 'mr' => 'गटारे, सांडपाणी निचरा व डासांचा त्रास'],
                        'type' => 'single_choice',
                        'required' => true,
                        'options' => [
                            ['label' => ['en' => 'Clean & Regularly Covered Gutters', 'mr' => 'स्वच्छ व झाकलेली भूमिगत गटारे']],
                            ['label' => ['en' => 'Frequent Choking / Sewage on Road', 'mr' => 'नेहमी तुंबतात व सांडपाणी रस्त्यावर वाहते']],
                            ['label' => ['en' => 'No Proper Drainage Line Exists', 'mr' => 'पक्की गटार लाईनच नाही']],
                        ],
                    ],
                    [
                        'id' => 'street_light',
                        'label' => ['en' => 'Street Lighting & Dark Spots in Lane', 'mr' => 'गल्लीतील पथदिवे (स्ट्रीट लाईट) स्थिती'],
                        'type' => 'single_choice',
                        'required' => false,
                        'options' => [
                            ['label' => ['en' => 'All Streetlights Working Properly', 'mr' => 'सर्व दिवे व्यवस्थित चालू आहेत']],
                            ['label' => ['en' => 'Multiple Faulty Lights / Dark Spots', 'mr' => 'अनेक दिवे बंद / रात्री अंधार असतो']],
                            ['label' => ['en' => 'No Electric Poles or Lights in Lane', 'mr' => 'गल्लीत खांब व दिवेच नाहीत']],
                        ],
                    ],
                    [
                        'id' => 'garbage_van',
                        'label' => ['en' => 'Door-to-Door Garbage Collection Van', 'mr' => 'कचरा गोळा करणारी घंटागाडी नियमितता'],
                        'type' => 'single_choice',
                        'required' => false,
                        'options' => [
                            ['label' => ['en' => 'Comes Daily on Fixed Time', 'mr' => 'दररोज ठरलेल्या वेळेवर येते']],
                            ['label' => ['en' => 'Irregular (Every 2-3 Days)', 'mr' => 'अनियमित / २-३ दिवसांतून एकदा येते']],
                            ['label' => ['en' => 'Does Not Come / Open Garbage Dumping', 'mr' => 'गाडी येत नाही / उघड्यावर कचरा टाकावा लागतो']],
                        ],
                    ],
                    [
                        'id' => 'ration_shop',
                        'label' => ['en' => 'Fair Price Ration Shop (रास्त भाव धान्य दुकान)', 'mr' => 'रेशन दुकानातील धान्य वाटप व गुणवत्ता'],
                        'type' => 'single_choice',
                        'required' => false,
                        'options' => [
                            ['label' => ['en' => 'Full Quota & Good Quality Grain', 'mr' => 'पूर्ण धान्य व चांगली गुणवत्ता मिळते']],
                            ['label' => ['en' => 'Shortage / Biometric Thumb Issues', 'mr' => 'धान्य कमी मिळते / बायोमॅट्रिक अडचण येते']],
                            ['label' => ['en' => 'Rude Behavior / Substandard Grains', 'mr' => 'खराब धान्य / उद्धट वर्तणूक']],
                        ],
                    ],
                    [
                        'id' => 'booth_worker_contact',
                        'label' => ['en' => 'Local Booth Worker / Panna Pramukh Contact', 'mr' => 'स्थानिक बूथ कार्यकर्ता / पन्ना प्रमुख यांच्याशी संपर्क आहे का?'],
                        'type' => 'single_choice',
                        'required' => false,
                        'options' => [
                            ['label' => ['en' => 'Yes, Active & Know Person Well', 'mr' => 'होय, संपर्कात आहेत व ओळखतात']],
                            ['label' => ['en' => 'Met Once or Twice', 'mr' => 'एक-दोनदा भेट झाली आहे']],
                            ['label' => ['en' => 'No Contact / Do not know who it is', 'mr' => 'काहीही संपर्क नाही / कोण आहेत माहित नाही']],
                        ],
                    ],
                ],
                'issues' => [
                    ['id' => 'water_pressure', 'label' => ['en' => 'Drinking Water Tap Regularity', 'mr' => 'नळाचे पाणी वारंवारता व दाब', 'hi' => 'पेयजल'], 'icon' => '💧'],
                    ['id' => 'drainage', 'label' => ['en' => 'Choked Gutter & Mosquito Menace', 'mr' => 'तुंबलेली गटारे व डासांचा प्रादुर्भाव', 'hi' => 'नाली सफाई'], 'icon' => '🚯'],
                    ['id' => 'internal_roads', 'label' => ['en' => 'Internal Lane Paving & Potholes', 'mr' => 'गल्लीतील सिमेंट रस्ते व पेव्हर ब्लॉक', 'hi' => 'गली पक्कीकरण'], 'icon' => '🧱'],
                    ['id' => 'street_lighting', 'label' => ['en' => 'Night Streetlight Dark Spots', 'mr' => 'रात्रीच्या वेळी पथदिवे बंद असणे', 'hi' => 'स्ट्रीट लाइट'], 'icon' => '💡'],
                    ['id' => 'garbage_cleanup', 'label' => ['en' => 'Waste Clearance & Garbage Bins', 'mr' => 'कचरा कुंडी स्वच्छता व दुर्गंधी', 'hi' => 'कचरा प्रबंधन'], 'icon' => '🗑️'],
                    ['id' => 'ration_pds', 'label' => ['en' => 'Ration Grain Allocation Issues', 'mr' => 'रेशन दुकानातील धान्य वाटप तक्रारी', 'hi' => 'राशन दुकान'], 'icon' => '🍚'],
                ],
                'customQuestions' => [],
            ],

            'GRADUATE_CONSTITUENCY' => [
                'formTitle' => ['en' => 'Maharashtra Council Graduate Constituency Survey', 'mr' => 'विधानपरिषद पदवीधर मतदारसंघ व युवक रोजगार पाहणी'],
                'campaignObjective' => 'graduate_council_campaign',
                'targetAudience' => 'graduates_youth',
                'modules' => [
                    'location' => true,
                    'electoral' => true,
                    'demographics' => true,
                    'personalInfo' => true,
                    'educationOccupation' => true,
                    'voterElectoral' => true,
                    'scopeSpecialized' => true,
                    'issuesPriorities' => true,
                    'publicServices' => true,
                    'communicationOutreach' => true,
                    'volunteerEnrollment' => true,
                    'openFeedback' => true,
                    'consent' => true,
                ],
                'specializedTitle' => ['en' => '🎓 Graduate Voter Enrollment (Form 18) & Youth Employment', 'mr' => '🎓 पदवीधर मतदार नोंदणी (नमुना १८) व रोजगार प्रश्न'],
                'specializedQuestions' => [
                    [
                        'id' => 'form_18_status',
                        'label' => ['en' => 'Form 18 Graduate Voter Roll Registration Status', 'mr' => 'नमुना १८ (पदवीधर मतदार नोंदणी) स्थिती'],
                        'type' => 'single_choice',
                        'required' => true,
                        'options' => [
                            ['label' => ['en' => 'Already Registered Graduate Voter', 'mr' => 'नोंदणीकृत पदवीधर मतदार आहे']],
                            ['label' => ['en' => 'Form 18 Submitted, Verification Pending', 'mr' => 'नमुना १८ अर्ज भरला आहे, पडताळणी बाकी']],
                            ['label' => ['en' => 'Want to Register (Need Field Assistance)', 'mr' => 'नोंदणी करायची आहे (मदत हवी आहे)']],
                            ['label' => ['en' => 'Not Aware / Did not Register', 'mr' => 'माहिती नाही / नोंदणी केलेली नाही']],
                        ],
                    ],
                    [
                        'id' => 'degree_level',
                        'label' => ['en' => 'Highest Educational Degree Obtained', 'mr' => 'सर्वोच्च शैक्षणिक पदवी'],
                        'type' => 'single_choice',
                        'required' => true,
                        'options' => [
                            ['label' => ['en' => 'B.A. / B.Com / B.Sc', 'mr' => 'बी.ए. / बी.कॉम / बी.एससी']],
                            ['label' => ['en' => 'B.E. / B.Tech / Engineering', 'mr' => 'अभियांत्रिकी (B.E. / B.Tech)']],
                            ['label' => ['en' => 'Medical / Pharmacy (MBBS, BAMS, B.Pharm)', 'mr' => 'वैद्यकीय / फार्मसी']],
                            ['label' => ['en' => 'Law / LL.B.', 'mr' => 'विधी (LL.B.)']],
                            ['label' => ['en' => 'B.Ed. / D.El.Ed.', 'mr' => 'शिक्षणशास्त्र (B.Ed.)']],
                            ['label' => ['en' => 'Master Degree (M.A., M.Sc, M.Com, M.Tech, MBA)', 'mr' => 'पदव्युत्तर (Post-Graduate)']],
                            ['label' => ['en' => 'Ph.D. / Research Scholar', 'mr' => 'पीएच.डी. / संशोधक']],
                        ],
                    ],
                    [
                        'id' => 'employment_status',
                        'label' => ['en' => 'Current Employment / Career Status', 'mr' => 'सध्याचा व्यवसाय / नोकरी स्थिती'],
                        'type' => 'single_choice',
                        'required' => true,
                        'options' => [
                            ['label' => ['en' => 'IT / Corporate Sector Employee', 'mr' => 'आयटी / कॉर्पोरेट क्षेत्रात नोकरी']],
                            ['label' => ['en' => 'Private Sector Company Job', 'mr' => 'खाजगी कंपनीत नोकरी']],
                            ['label' => ['en' => 'Competitive Exam Aspirant (MPSC/UPSC/Govt)', 'mr' => 'स्पर्धा परीक्षा तयारी (MPSC/UPSC/सरळसेवा)']],
                            ['label' => ['en' => 'Business / Entrepreneur / Self-Employed', 'mr' => 'स्वतःचा व्यवसाय / स्टार्टअप']],
                            ['label' => ['en' => 'Unemployed / Actively Seeking Employment', 'mr' => 'सुशिक्षित बेरोजगार / नोकरीच्या शोधात']],
                        ],
                    ],
                    [
                        'id' => 'mpsc_paper_leak',
                        'label' => ['en' => 'Priority Demand for MPSC & Govt Recruitment', 'mr' => 'स्पर्धा परीक्षा व नोकरभरतीबाबत प्रमुख मागणी'],
                        'type' => 'single_choice',
                        'required' => true,
                        'options' => [
                            ['label' => ['en' => 'Strict Anti-Paper Leak Law & Transparent Exam Calendar', 'mr' => 'पेपरफुटी विरोधी कठोर कायदा व वेळेत परीक्षा वेळापत्रक']],
                            ['label' => ['en' => 'Mega Bharti for 75,000+ Vacant Govt Posts', 'mr' => 'रिक्त ७५,०००+ पदांची तात्काळ मेगा भरती']],
                            ['label' => ['en' => 'Complete Ban on Contractual Recruitment in Govt Depts', 'mr' => 'शासकीय खात्यातील कंत्राटी भरती पद्धती कायमची बंद करा']],
                            ['label' => ['en' => 'IT Parks & High-Paying Employment in District', 'mr' => 'जिल्ह्यात आयटी पार्क व स्थानिक रोजगार']],
                        ],
                    ],
                ],
                'issues' => [
                    ['id' => 'youth_jobs', 'label' => ['en' => 'White-Collar & IT Jobs in Region', 'mr' => 'स्थानिक भागात आयटी व खाजगी रोजगार', 'hi' => 'आईटी व निजी क्षेत्र नौकरियां'], 'icon' => '💻'],
                    ['id' => 'mpsc_recruitment', 'label' => ['en' => 'MPSC Exam Regularity & Anti-Paper Leak', 'mr' => 'MPSC व स्पर्धा परीक्षांचे वेळापत्रक व पारदर्शकता', 'hi' => 'परीक्षा नियमितता'], 'icon' => '📑'],
                    ['id' => 'college_fees', 'label' => ['en' => 'Higher Education Fee Control & Scholarships', 'mr' => 'उच्च शिक्षण फी नियंत्रण व वेळेवर स्कॉलरशिप', 'hi' => 'फीस नियंत्रण'], 'icon' => '🎓'],
                    ['id' => 'startup_support', 'label' => ['en' => 'Startup Incubation & Youth Loans', 'mr' => 'स्टार्टअप प्रोत्साहन व बिनव्याजी कर्ज योजना', 'hi' => 'स्टार्टअप ऋण'], 'icon' => '🚀'],
                    ['id' => 'prof_recruitment', 'label' => ['en' => 'Assistant Professor Recruitment (CHB to Permanent)', 'mr' => 'सहाय्यक प्राध्यापक भरती (घड्याळी तासिका ते कायम)', 'hi' => 'प्रोफेसर भर्ती'], 'icon' => '👨‍🏫'],
                    ['id' => 'unemployment_allowance', 'label' => ['en' => 'Unemployment Allowance for Graduates', 'mr' => 'सुशिक्षित बेरोजगारांना मासिक भत्ता', 'hi' => 'बेरोजगारी भत्ता'], 'icon' => '💵'],
                ],
                'customQuestions' => [],
            ],

            'TEACHER_CONSTITUENCY' => [
                'formTitle' => ['en' => 'Maharashtra Council Teacher Constituency Survey', 'mr' => 'विधानपरिषद शिक्षक मतदारसंघ व शैक्षणिक हक्क पाहणी'],
                'campaignObjective' => 'teacher_council_campaign',
                'targetAudience' => 'teachers_professors',
                'modules' => [
                    'location' => true,
                    'electoral' => true,
                    'demographics' => true,
                    'personalInfo' => true,
                    'educationOccupation' => true,
                    'voterElectoral' => true,
                    'scopeSpecialized' => true,
                    'issuesPriorities' => true,
                    'publicServices' => true,
                    'communicationOutreach' => true,
                    'volunteerEnrollment' => true,
                    'openFeedback' => true,
                    'consent' => true,
                ],
                'specializedTitle' => ['en' => '📚 Teacher Voter Registration (Form 19) & Academic Demands', 'mr' => '📚 शिक्षक मतदार नोंदणी (नमुना १९) व प्रलंबित मागण्या'],
                'specializedQuestions' => [
                    [
                        'id' => 'form_19_status',
                        'label' => ['en' => 'Form 19 Teacher Voter Roll Registration Status', 'mr' => 'नमुना १९ (शिक्षक मतदार नोंदणी) स्थिती'],
                        'type' => 'single_choice',
                        'required' => true,
                        'options' => [
                            ['label' => ['en' => 'Already Approved Teacher Voter', 'mr' => 'मान्यताप्राप्त शिक्षक मतदार आहे']],
                            ['label' => ['en' => 'Form 19 Submitted, Awaiting Approval', 'mr' => 'नमुना १९ अर्ज दाखल, मंजुरीची प्रतीक्षा']],
                            ['label' => ['en' => 'Needs Guidance for Form 19 Enrollment', 'mr' => 'नोंदणीसाठी मार्गदर्शन हवे आहे']],
                            ['label' => ['en' => 'Not Registered Yet', 'mr' => 'नोंदणी केलेली नाही']],
                        ],
                    ],
                    [
                        'id' => 'institution_level',
                        'label' => ['en' => 'Teaching Institution Level', 'mr' => 'शाळा / महाविद्यालयाचा स्तर'],
                        'type' => 'single_choice',
                        'required' => true,
                        'options' => [
                            ['label' => ['en' => 'Primary School (प्राथमिक)', 'mr' => 'प्राथमिक शाळा']],
                            ['label' => ['en' => 'Secondary School (माध्यमिक)', 'mr' => 'माध्यमिक शाळा']],
                            ['label' => ['en' => 'Higher Secondary / Jr. College (कनिष्ठ महाविद्यालय)', 'mr' => 'कनिष्ठ महाविद्यालय (११ वी / १२ वी)']],
                            ['label' => ['en' => 'Senior Degree College / University (वरिष्ठ महाविद्यालय)', 'mr' => 'वरिष्ठ पदवी महाविद्यालय / विद्यापीठ']],
                        ],
                    ],
                    [
                        'id' => 'grant_status',
                        'label' => ['en' => 'School / College Grant-in-aid Status', 'mr' => 'शाळा / तुकडीचे अनुदान स्वरूप'],
                        'type' => 'single_choice',
                        'required' => true,
                        'options' => [
                            ['label' => ['en' => '100% Fully Aided (१००% अनुदानित)', 'mr' => '१००% शासन अनुदानित']],
                            ['label' => ['en' => 'Partially Aided / Tappa (टप्पा अनुदान - २०% ते ८०%)', 'mr' => 'टप्पा अनुदान (२०%/४०%/६०%/८०%)']],
                            ['label' => ['en' => 'Permanently Unaided (कायम विनाअनुदानित)', 'mr' => 'विनाअनुदानित']],
                            ['label' => ['en' => 'Self-Financed / Private CBSE/ICSE', 'mr' => 'स्वयंअर्थसहाय्यित / खाजगी']],
                            ['label' => ['en' => 'Zilla Parishad / Municipal School', 'mr' => 'जिल्हा परिषद / मनपा शाळा']],
                        ],
                    ],
                    [
                        'id' => 'ops_pension',
                        'label' => ['en' => 'Old Pension Scheme (OPS - जुनी पेन्शन योजना) Stance', 'mr' => 'जुनी पेन्शन योजना (OPS) बाबत ठाम भूमिका'],
                        'type' => 'single_choice',
                        'required' => true,
                        'options' => [
                            ['label' => ['en' => 'Demand Immediate OPS Implementation', 'mr' => 'जुनी पेन्शन योजना (OPS) तात्काळ पूर्ववत लागू व्हावी']],
                            ['label' => ['en' => 'Accept Unified/Modified Pension with Guaranteed 50%', 'mr' => '५०% हमीसह सुधारीत पेन्शन मान्य आहे']],
                            ['label' => ['en' => 'NPS Scheme is Sufficient', 'mr' => 'सध्याची NPS योजना योग्य वाटते']],
                        ],
                    ],
                ],
                'issues' => [
                    ['id' => 'ops_implementation', 'label' => ['en' => 'Old Pension Scheme (OPS) Restoration', 'mr' => 'जुनी पेन्शन योजना (OPS) पूर्ववत करणे', 'hi' => 'पुरानी पेंशन योजना'], 'icon' => '📜'],
                    ['id' => 'pending_grants', 'label' => ['en' => 'Release of 100% Grant to Tappa Schools', 'mr' => 'टप्पा शाळांना १००% अनुदान तात्काळ देणे', 'hi' => 'स्कूल अनुदान'], 'icon' => '🏫'],
                    ['id' => 'non_academic_work', 'label' => ['en' => 'Exempt Teachers from Non-Academic Census/App Work', 'mr' => 'शिक्षकांना अशैक्षणिक व विविध ॲप कामांतून मुक्ती', 'hi' => 'गैर शैक्षणिक कार्य मुक्ति'], 'icon' => '🚫'],
                    ['id' => 'tet_approvals', 'label' => ['en' => 'Individual Approval & Service Security (वैयक्तिक मान्यता)', 'mr' => 'शिक्षकांची वैयक्तिक मान्यता व सेवा संरक्षण', 'hi' => 'सेवा सुरक्षा'], 'icon' => '✍️'],
                    ['id' => 'contractual_bans', 'label' => ['en' => 'Ban on Contractual Clock Hour Basis (CHB) Staff', 'mr' => 'कंत्राटी तासिका (CHB) पद्धत बंद करून कायम नियुक्ती', 'hi' => 'संविदा भर्ती रोक'], 'icon' => '⚖️'],
                    ['id' => 'medical_cashless', 'label' => ['en' => 'Cashless Mediclaim for Teachers & Families', 'mr' => 'शिक्षक व कुटुंबीयांसाठी कॅशलेस वैद्यकीय विमा', 'hi' => 'कैशलेस स्वास्थ्य बीमा'], 'icon' => '🩺'],
                ],
                'customQuestions' => [],
            ],
        ];
    }

    public function run(): void
    {
        $templates = self::getDefaultTemplates();

        foreach ($templates as $scope => $tpl) {
            SurveyForm::updateOrCreate(
                [
                    'scope' => $scope,
                    'record_type' => 'TEMPLATE',
                ],
                [
                    'form_code' => 'TPL_' . $scope,
                    'title_en' => $tpl['formTitle']['en'] ?? $scope . ' Survey',
                    'title_mr' => $tpl['formTitle']['mr'] ?? $scope . ' पाहणी',
                    'campaign_objective' => $tpl['campaignObjective'] ?? null,
                    'target_audience' => $tpl['targetAudience'] ?? null,
                    'specialized_title' => $tpl['specializedTitle'] ?? null,
                    'modules' => $tpl['modules'] ?? [],
                    'specialized_questions' => $tpl['specializedQuestions'] ?? [],
                    'custom_questions' => $tpl['customQuestions'] ?? [],
                    'issues' => $tpl['issues'] ?? [],
                    'communication_channels' => [],
                    'form_data' => $tpl,
                    'status' => 'ACTIVE',
                ]
            );
        }
    }
}
