<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KarkartaEvmSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Election Master
        $electionId = DB::table('karkarta_evm_elections')->insertGetId([
            'worker_id'       => 1,
            'name'            => '२८२ - सांगली विधानसभा / नगर परिषद निवडणूक २०२६ (EVM Demo)',
            'message'         => 'घड्याळ चिन्हासमोरील क्रमांक २ बटण दाबून "सौ. वंदना चंद्रकांत परब" यांना प्रचंड मतांनी विजयी करा!',
            'date_time'       => 'मतदान दिनांक : २० नोव्हेंबर, २०२६ रोजी सकाळी ७:०० ते सायंकाळी ६:००',
            'whatsapp_number' => '919822011231',
            'status'          => 'ACTIVE',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // 2. Settings
        DB::table('karkarta_evm_settings')->insert([
            'worker_id'            => 1,
            'enable_sound'         => true,
            'enable_vibration'     => true,
            'allow_multiple_votes' => true,
            'contact_whatsapp'     => '9822011231',
            'contact_call'         => '9822011231',
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        // 3. Candidates for Ballot Unit
        $candidates = [
            [
                'serial_number'   => 1,
                'candidate_name'  => 'राजेश विठ्ठल पाटील',
                'party_name'      => 'भारतीय जनता पार्टी (BJP)',
                'party_symbol'    => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/1e/Bharatiya_Janata_Party_logo.svg/300px-Bharatiya_Janata_Party_logo.svg.png',
                'candidate_photo' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&h=150&fit=crop&crop=face',
                'position'        => 'विधानसभा आमदार',
                'section_color'   => '#ea580c',
                'highlighted'     => false,
                'voice_message'   => 'आपले मत राजेश पाटील यांना नोंदवले गेले आहे.',
                'slug'            => 'rajesh-patil',
                'votes'           => 14,
            ],
            [
                'serial_number'   => 2,
                'candidate_name'  => 'सौ. वंदना चंद्रकांत परब',
                'party_name'      => 'राष्ट्रवादी काँग्रेस पक्ष (NCP)',
                'party_symbol'    => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e4/Nationalist_Congress_Party_Election_Symbol.svg/300px-Nationalist_Congress_Party_Election_Symbol.svg.png',
                'candidate_photo' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150&h=150&fit=crop&crop=face',
                'position'        => 'विधानसभा आमदार',
                'section_color'   => '#ea580c',
                'highlighted'     => true,
                'voice_message'   => 'राष्ट्रवादी काँग्रेस पक्षाच्या अधिकृत उमेदवार सौ. वंदना चंद्रकांत परब यांना घड्याळ चिन्हासमोरील बटण दाबल्याबद्दल मनापासून धन्यवाद!',
                'slug'            => 'vandana-parab',
                'votes'           => 85,
            ],
            [
                'serial_number'   => 3,
                'candidate_name'  => 'सुरेश महादेव सावंत',
                'party_name'      => 'भारतीय राष्ट्रीय काँग्रेस (INC)',
                'party_symbol'    => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/6c/Indian_National_Congress_hand_logo.svg/300px-Indian_National_Congress_hand_logo.svg.png',
                'candidate_photo' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&h=150&fit=crop&crop=face',
                'position'        => 'विधानसभा आमदार',
                'section_color'   => '#ea580c',
                'highlighted'     => false,
                'voice_message'   => 'आपले मत सुरेश सावंत यांना नोंदवले गेले आहे.',
                'slug'            => 'suresh-sawant',
                'votes'           => 12,
            ],
            [
                'serial_number'   => 4,
                'candidate_name'  => 'संजय रामचंद्र माने',
                'party_name'      => 'शिवसेना (उद्धव बाळासाहेब ठाकरे)',
                'party_symbol'    => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/87/Shiv_Sena_%28Uddhav_Balasaheb_Thackeray%29_Flaming_Torch_symbol.svg/240px-Shiv_Sena_%28Uddhav_Balasaheb_Thackeray%29_Flaming_Torch_symbol.svg.png',
                'candidate_photo' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&h=150&fit=crop&crop=face',
                'position'        => 'विधानसभा आमदार',
                'section_color'   => '#ea580c',
                'highlighted'     => false,
                'voice_message'   => 'आपले मत संजय माने यांना नोंदवले गेले आहे.',
                'slug'            => 'sanjay-mane',
                'votes'           => 18,
            ],
            [
                'serial_number'   => 5,
                'candidate_name'  => 'प्रमोद संभाजी कदम',
                'party_name'      => 'शिवसेना (एकनाथ शिंदे)',
                'party_symbol'    => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4b/Shiv_Sena_Bow_and_Arrow_symbol.svg/300px-Shiv_Sena_Bow_and_Arrow_symbol.svg.png',
                'candidate_photo' => 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=150&h=150&fit=crop&crop=face',
                'position'        => 'विधानसभा आमदार',
                'section_color'   => '#ea580c',
                'highlighted'     => false,
                'voice_message'   => 'आपले मत प्रमोद कदम यांना नोंदवले गेले आहे.',
                'slug'            => 'pramod-kadam',
                'votes'           => 9,
            ],
            [
                'serial_number'   => 6,
                'candidate_name'  => 'तानाजी विष्णू सूर्यवंशी',
                'party_name'      => 'राष्ट्रवादी काँग्रेस (शरदचंद्र पवार)',
                'party_symbol'    => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/1d/NCP_Sharadchandra_Pawar_Symbol.png/300px-NCP_Sharadchandra_Pawar_Symbol.png',
                'candidate_photo' => 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=150&h=150&fit=crop&crop=face',
                'position'        => 'विधानसभा आमदार',
                'section_color'   => '#ea580c',
                'highlighted'     => false,
                'voice_message'   => 'आपले मत तानाजी सूर्यवंशी यांना नोंदवले गेले आहे.',
                'slug'            => 'tanaji-suryavanshi',
                'votes'           => 21,
            ],
            [
                'serial_number'   => 7,
                'candidate_name'  => 'अरुण तुकाराम कांबळे',
                'party_name'      => 'वंचित बहुजन आघाडी (VBA)',
                'party_symbol'    => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/52/Gas_cylinder_symbol.svg/200px-Gas_cylinder_symbol.svg.png',
                'candidate_photo' => 'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?w=150&h=150&fit=crop&crop=face',
                'position'        => 'विधानसभा आमदार',
                'section_color'   => '#ea580c',
                'highlighted'     => false,
                'voice_message'   => 'आपले मत अरुण कांबळे यांना नोंदवले गेले आहे.',
                'slug'            => 'arun-kamble',
                'votes'           => 7,
            ],
            [
                'serial_number'   => 8,
                'candidate_name'  => 'नोटा (NOTA - वरीलपैकी कोणीही नाही)',
                'party_name'      => 'None of the Above (NOTA)',
                'party_symbol'    => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a2/NOTA_Option_Logo.svg/240px-NOTA_Option_Logo.svg.png',
                'candidate_photo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a2/NOTA_Option_Logo.svg/240px-NOTA_Option_Logo.svg.png',
                'position'        => 'विधानसभा आमदार',
                'section_color'   => '#64748b',
                'highlighted'     => false,
                'voice_message'   => 'आपण नोटा पर्यायाला मतदान केले आहे.',
                'slug'            => 'nota',
                'votes'           => 3,
            ],
        ];

        foreach ($candidates as $c) {
            DB::table('karkarta_evm_candidates')->insert([
                'election_id'      => $electionId,
                'serial_number'    => $c['serial_number'],
                'candidate_name'   => $c['candidate_name'],
                'party_name'       => $c['party_name'],
                'party_symbol'     => $c['party_symbol'],
                'candidate_photo'  => $c['candidate_photo'],
                'position'         => $c['position'],
                'section_color'    => $c['section_color'],
                'highlighted'      => $c['highlighted'],
                'voice_message'    => $c['voice_message'],
                'slug'             => $c['slug'],
                'votes'            => $c['votes'],
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }
    }
}
