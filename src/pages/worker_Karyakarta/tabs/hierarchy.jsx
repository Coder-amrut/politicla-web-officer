import React from 'react';
import { Compass } from 'lucide-react';

export default function HierarchyTab({ isMr }) {
  const hierarchyLevels = [
    { level: 1, name_mr: 'राज्य (State)', name_en: 'State', value_mr: 'महाराष्ट्र (Maharashtra)', code: 'MH-27', icon: '🏛️', desc_mr: 'राज्यस्तरीय निवडणूक क्षेत्र' },
    { level: 2, name_mr: 'जिल्हा (District)', name_en: 'District', value_mr: 'सांगली (Sangli)', code: 'DIST-31', icon: '🏢', desc_mr: 'जिल्हा परिषद व मुख्यालय क्षेत्र' },
    { level: 3, name_mr: 'विधानसभा (Assembly)', name_en: 'Assembly', value_mr: '२८२ सांगली विधानसभा मतदारसंघ', code: 'AC-282', icon: '🗳️', desc_mr: 'विधानसभा निवडणूक कार्यक्षेत्र' },
    { level: 4, name_mr: 'तालुका / प्रभाग (Taluka / Ward)', name_en: 'Taluka / Ward', value_mr: 'मिरज तालुका / प्रभाग क्रमांक १२', code: 'TAL-MIRAJ', icon: '📍', desc_mr: 'स्थानिक स्वराज्य संस्था प्रभाग' },
    { level: 5, name_mr: 'गाव / शहर परिसर (Village / Area)', name_en: 'Village / Area', value_mr: 'सांगली शहर मध्य / वॉर्ड परिसर', code: 'VIL-SANGLI', icon: '🏘️', desc_mr: 'नागरी वस्ती व लोकवस्ती कार्यक्षेत्र' },
    { level: 6, name_mr: 'बूथ / भाग (Booth / Part)', name_en: 'Booth / Part', value_mr: 'बूथ क्र. १ ते १४ (प्राथमिक शाळा केंद्र)', code: 'BOOTH-12/1', icon: '🎯', desc_mr: 'अधिकाऱ्याचे प्रत्यक्ष नियुक्त मतदान केंद्र' },
  ];

  return (
    <div className="module-container" style={{ display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>
      {/* Header */}
      <div className="page-header" style={{ marginBottom: 0 }}>
        <div>
          <h1 className="page-title" style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
            <Compass size={28} color="#0f766e" />
            <span>{isMr ? '६-स्तरीय क्षेत्ररचना दर्शक (Geographic Hierarchy)' : '6-Level Geography Hierarchy'}</span>
          </h1>
          <div className="page-subtitle">
            {isMr
              ? 'राज्य ➔ जिल्हा ➔ विधानसभा ➔ तालुका ➔ गाव ➔ बूथ/भाग अशी संपूर्ण ६-स्तरीय प्रशासकीय क्षेत्ररचना.'
              : 'Full 6-level geographic structure: State -> District -> Assembly -> Taluka -> Village -> Booth.'}
          </div>
        </div>
      </div>

      {/* Interactive Breadcrumb Strip */}
      <div style={{
        background: 'linear-gradient(135deg, #0f2b5c 0%, #000080 100%)',
        color: '#ffffff',
        borderRadius: '14px',
        padding: '18px 22px',
        boxShadow: '0 4px 14px rgba(0, 0, 128, 0.15)',
        display: 'flex',
        flexDirection: 'column',
        gap: '12px'
      }}>
        <div style={{ fontSize: '11px', textTransform: 'uppercase', letterSpacing: '0.6px', color: '#93c5fd', fontWeight: 800 }}>
          🇮🇳 {isMr ? 'तुमची नियुक्त क्षेत्र शृंखला (Assigned Hierarchy Chain)' : 'Your Assigned Hierarchy Chain'}
        </div>
        <div style={{ display: 'flex', alignItems: 'center', flexWrap: 'wrap', gap: '8px', fontSize: '13px', fontWeight: 700 }}>
          <span style={{ background: 'rgba(255,255,255,0.15)', padding: '4px 10px', borderRadius: '6px' }}>महाराष्ट्र (राज्य)</span>
          <span style={{ color: '#FF9933' }}>➔</span>
          <span style={{ background: 'rgba(255,255,255,0.15)', padding: '4px 10px', borderRadius: '6px' }}>सांगली (जिल्हा)</span>
          <span style={{ color: '#FF9933' }}>➔</span>
          <span style={{ background: 'rgba(255,255,255,0.15)', padding: '4px 10px', borderRadius: '6px' }}>२८२ सांगली (विधानसभा)</span>
          <span style={{ color: '#FF9933' }}>➔</span>
          <span style={{ background: 'rgba(255,255,255,0.15)', padding: '4px 10px', borderRadius: '6px' }}>मिरज / प्रभाग १२</span>
          <span style={{ color: '#FF9933' }}>➔</span>
          <span style={{ background: 'rgba(255,255,255,0.15)', padding: '4px 10px', borderRadius: '6px' }}>सांगली शहर</span>
          <span style={{ color: '#10b981' }}>➔</span>
          <span style={{ background: '#10b981', color: '#ffffff', padding: '4px 12px', borderRadius: '6px', boxShadow: '0 2px 6px rgba(16,185,129,0.3)' }}>बूथ क्र. १ ते १४ (नियुक्त)</span>
        </div>
      </div>

      {/* 6 Level Cards Grid */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(320px, 1fr))', gap: '16px' }}>
        {hierarchyLevels.map((lvl) => (
          <div
            key={lvl.level}
            className="card"
            style={{
              padding: '18px',
              borderRadius: '14px',
              border: lvl.level === 6 ? '2px solid #10b981' : '1px solid #e2e8f0',
              background: lvl.level === 6 ? '#f0fdf4' : '#ffffff',
              boxShadow: lvl.level === 6 ? '0 4px 12px rgba(16, 185, 129, 0.12)' : '0 2px 6px rgba(0,0,0,0.03)',
              display: 'flex',
              flexDirection: 'column',
              gap: '10px'
            }}
          >
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                <span style={{ fontSize: '20px' }}>{lvl.icon}</span>
                <span style={{
                  fontSize: '11px',
                  fontWeight: 800,
                  color: lvl.level === 6 ? '#15803d' : '#2563eb',
                  background: lvl.level === 6 ? '#dcfce7' : '#eff6ff',
                  padding: '2px 8px',
                  borderRadius: '10px',
                  textTransform: 'uppercase'
                }}>
                  {isMr ? `स्तर ${lvl.level}` : `Level ${lvl.level}`}
                </span>
              </div>

              <span style={{ fontFamily: 'monospace', fontSize: '11px', color: '#94a3b8', fontWeight: 700 }}>
                {lvl.code}
              </span>
            </div>

            <div>
              <div style={{ fontSize: '12px', color: '#64748b', fontWeight: 700 }}>{isMr ? lvl.name_mr : lvl.name_en}</div>
              <div style={{ fontSize: '16px', fontWeight: 800, color: '#0f172a', marginTop: '2px' }}>{lvl.value_mr}</div>
            </div>

            <div style={{ fontSize: '12px', color: '#64748b', lineHeight: 1.4, paddingTop: '8px', borderTop: '1px solid rgba(0,0,0,0.06)' }}>
              {lvl.desc_mr}
            </div>

            {lvl.level === 6 && (
              <div style={{ background: '#dcfce7', color: '#166534', padding: '6px 10px', borderRadius: '6px', fontSize: '11.5px', fontWeight: 750, display: 'flex', alignItems: 'center', gap: '6px' }}>
                <span>✓</span>
                <span>{isMr ? 'तुमचे प्रत्यक्ष कार्यक्षेत्र येथे नियुक्त आहे' : 'Your active field duty is assigned here'}</span>
              </div>
            )}
          </div>
        ))}
      </div>
    </div>
  );
}
