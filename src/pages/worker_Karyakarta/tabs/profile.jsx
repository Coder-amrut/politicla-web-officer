import React, { useState, useRef } from 'react';
import {
  X,
  Save,
  Edit2,
  MapPin,
  Phone,
  User,
  Mail,
  Shield,
  Camera,
  Trash2
} from 'lucide-react';

export default function ProfileTab({
  isMr,
  t,
  user,
  profileData = {},
  isEditingProfile,
  setIsEditingProfile,
  handleProfileChange,
  saveProfile,
  dashboardStats = { totalAssigned: 0, contactRate: 0, contacted: 0, surveysCompleted: 0 }
}) {
  const [profilePhoto, setProfilePhoto] = useState(() => {
    return profileData.photoUrl || localStorage.getItem('karyakarta_profile_photo') || null;
  });
  const fileInputRef = useRef(null);

  const handlePhotoUpload = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
      alert(isMr ? 'कृपया ५ MB पेक्षा लहान फोटो निवडा.' : 'Please select a photo smaller than 5 MB.');
      return;
    }

    const reader = new FileReader();
    reader.onload = (event) => {
      const dataUrl = event.target.result;
      setProfilePhoto(dataUrl);
      try {
        localStorage.setItem('karyakarta_profile_photo', dataUrl);
      } catch (err) {
        console.warn('Could not save photo to localStorage', err);
      }
      if (handleProfileChange) {
        handleProfileChange({ target: { name: 'photoUrl', value: dataUrl } });
      }
    };
    reader.readAsDataURL(file);
  };

  const handleRemovePhoto = (e) => {
    e.stopPropagation();
    if (window.confirm(isMr ? 'फोटो काढून टाकायचा आहे का?' : 'Remove profile photo?')) {
      setProfilePhoto(null);
      localStorage.removeItem('karyakarta_profile_photo');
      if (handleProfileChange) {
        handleProfileChange({ target: { name: 'photoUrl', value: '' } });
      }
    }
  };

  return (
    <div className="module-container" style={{ display: 'flex', flexDirection: 'column', gap: '20px' }}>
      {/* Subheader & Action Bar */}
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '12px' }}>
        <div>
          <div style={{ fontSize: '20px', fontWeight: '800', color: '#0f172a', display: 'flex', alignItems: 'center', gap: '8px' }}>
            <span>👤 {isMr ? 'प्रोफाइल' : 'Profile'}</span>

          </div>

        </div>

        <div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
          {isEditingProfile ? (
            <>
              <button
                type="button"
                className="karyakarta-btn karyakarta-btn-outline"
                onClick={() => setIsEditingProfile(false)}
              >
                <X size={15} />
                <span>{t('cancel')}</span>
              </button>
              <button
                type="button"
                className="karyakarta-btn karyakarta-btn-success"
                onClick={saveProfile}
              >
                <Save size={15} />
                <span>{t('saveChanges')}</span>
              </button>
            </>
          ) : (
            <>
              <button
                type="button"
                className="karyakarta-btn karyakarta-btn-primary"
                onClick={() => setIsEditingProfile(true)}
              >
                <Edit2 size={15} />
                <span>{t('editProfile')}</span>
              </button>
              <button
                type="button"
                className="karyakarta-btn karyakarta-btn-outline"
                onClick={() => window.print()}
                title={isMr ? 'ओळखपत्र प्रिंट करा' : 'Print ID Card'}
              >
                <span>🖨️</span>
                <span>{isMr ? 'प्रिंट / डाउनलोड' : 'Print ID'}</span>
              </button>
            </>
          )}
        </div>
      </div>

      {/* OFFICIAL DIGITAL ID CARD (डिजिटल ओळखपत्र) */}
      <div
        style={{
          background: '#ffffff',
          borderRadius: '16px',
          border: '1px solid #e2e8f0',
          overflow: 'hidden',
          boxShadow: '0 4px 14px rgba(15, 23, 42, 0.05)',
          position: 'relative',
        }}
      >
        {/* Top Indian Tricolor Stripe */}
        <div
          style={{
            height: '4px',
            background: 'linear-gradient(90deg, #FF671F 0%, #FFFFFF 50%, #138808 100%)',
          }}
        />

        {/* Card Header Background */}
        <div
          style={{
            background: 'linear-gradient(135deg, #0f2b5c 0%, #000080 60%, #1e3a8a 100%)',
            padding: '24px 28px',
            color: '#ffffff',
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            flexWrap: 'wrap',
            gap: '16px',
            position: 'relative',
          }}
        >
          <div style={{ display: 'flex', alignItems: 'center', gap: '20px', position: 'relative', zIndex: 2 }}>
            {/* Avatar with Status Ring & Photo Upload */}
            <div style={{ position: 'relative' }}>
              <input
                ref={fileInputRef}
                type="file"
                accept="image/*"
                style={{ display: 'none' }}
                onChange={handlePhotoUpload}
              />

              <div
                onClick={() => fileInputRef.current?.click()}
                style={{
                  width: '80px',
                  height: '80px',
                  borderRadius: '50%',
                  background: profilePhoto ? '#0f172a' : 'linear-gradient(135deg, #FF671F 0%, #ea580c 100%)',
                  color: '#ffffff',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  fontSize: '32px',
                  fontWeight: '900',
                  border: '3px solid #ffffff',
                  boxShadow: '0 4px 14px rgba(0,0,0,0.3)',
                  cursor: 'pointer',
                  overflow: 'hidden',
                  position: 'relative',
                  userSelect: 'none'
                }}
                title={isMr ? 'प्रोफाइल फोटो अपलोड करण्यासाठी क्लिक करा' : 'Click to upload profile photo'}
              >
                {profilePhoto ? (
                  <img
                    src={profilePhoto}
                    alt="Profile"
                    style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                  />
                ) : (
                  <span>{(profileData.fullName || user?.full_name || 'K').charAt(0)}</span>
                )}

                {/* Hover Camera Overlay */}
                <div
                  style={{
                    position: 'absolute',
                    inset: 0,
                    background: 'rgba(0,0,0,0.45)',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    opacity: 0,
                    transition: 'opacity 0.2s ease',
                  }}
                  onMouseEnter={(e) => (e.currentTarget.style.opacity = '1')}
                  onMouseLeave={(e) => (e.currentTarget.style.opacity = '0')}
                >
                  <Camera size={24} color="#ffffff" />
                </div>
              </div>

              {/* Camera Upload Button Badge */}
              <button
                type="button"
                onClick={() => fileInputRef.current?.click()}
                style={{
                  position: 'absolute',
                  top: '-4px',
                  right: '-4px',
                  width: '26px',
                  height: '26px',
                  borderRadius: '50%',
                  background: '#ffffff',
                  border: '1.5px solid #e2e8f0',
                  color: '#ea580c',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  cursor: 'pointer',
                  boxShadow: '0 2px 6px rgba(0,0,0,0.25)',
                  padding: 0
                }}
                title={isMr ? 'फोटो अपलोड करा' : 'Upload photo'}
              >
                <Camera size={13} />
              </button>

              {/* If photo exists, show a remove button */}
              {profilePhoto && (
                <button
                  type="button"
                  onClick={handleRemovePhoto}
                  style={{
                    position: 'absolute',
                    top: '-4px',
                    left: '-4px',
                    width: '24px',
                    height: '24px',
                    borderRadius: '50%',
                    background: '#ef4444',
                    border: '1.5px solid #ffffff',
                    color: '#ffffff',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    cursor: 'pointer',
                    boxShadow: '0 2px 6px rgba(0,0,0,0.2)',
                    padding: 0
                  }}
                  title={isMr ? 'फोटो हटवा' : 'Remove photo'}
                >
                  <Trash2 size={12} />
                </button>
              )}

              {/* Active Status Indicator */}
              <span
                style={{
                  position: 'absolute',
                  bottom: '2px',
                  right: '2px',
                  width: '18px',
                  height: '18px',
                  borderRadius: '50%',
                  background: '#10b981',
                  border: '2px solid #ffffff',
                  boxShadow: '0 0 6px #10b981'
                }}
                title={isMr ? 'सक्रिय अधिकारी' : 'Active Officer'}
              />
            </div>

            <div>
              <div style={{ display: 'flex', alignItems: 'center', gap: '8px', flexWrap: 'wrap' }}>
                <h2 style={{ fontSize: '22px', fontWeight: '900', margin: 0, color: '#ffffff' }}>
                  {profileData.fullName || user?.full_name || 'अधिकारी'}
                </h2>
                <span
                  style={{
                    fontSize: '11px',
                    fontWeight: '750',
                    padding: '2px 8px',
                    borderRadius: '12px',
                    background: 'rgba(255, 255, 255, 0.2)',
                    border: '1px solid rgba(255, 255, 255, 0.4)',
                    color: '#ffffff',
                  }}
                >
                  {profileData.employeeCode || 'WRK-0001'}
                </span>
              </div>

              <div style={{ fontSize: '13px', color: '#fed7aa', fontWeight: '700', marginTop: '3px' }}>
                🚩 {profileData.responsibility || (isMr ? 'बूथ समन्वयक व प्रभाग अधिकारी' : 'Ward & Booth Coordinator')}
              </div>

              <div style={{ display: 'flex', alignItems: 'center', gap: '14px', fontSize: '12px', color: '#cbd5e1', marginTop: '6px', flexWrap: 'wrap' }}>
                <span style={{ display: 'flex', alignItems: 'center', gap: '4px' }}>
                  <MapPin size={13} color="#f97316" /> {profileData.address || (isMr ? 'महाराष्ट्र' : 'Maharashtra')}
                </span>
                <span>•</span>
                <span style={{ display: 'flex', alignItems: 'center', gap: '4px' }}>
                  <Phone size={13} color="#10b981" /> {profileData.phone || user?.phone || '-'}
                </span>
              </div>
            </div>
          </div>

          {/* Badges Column */}
          <div style={{ display: 'flex', flexDirection: 'column', gap: '6px', alignItems: 'flex-end', position: 'relative', zIndex: 2 }}>
            <span
              style={{
                fontSize: '11.5px',
                fontWeight: '800',
                padding: '4px 10px',
                borderRadius: '8px',
                background: '#10b981',
                color: '#ffffff',
                display: 'flex',
                alignItems: 'center',
                gap: '5px',
                boxShadow: '0 2px 6px rgba(16, 185, 129, 0.3)',
              }}
            >
              <span>✓</span>
              <span>{isMr ? 'अधिकृत व सक्रिय' : 'Verified Active'}</span>
            </span>
            <span style={{ fontSize: '11px', color: '#cbd5e1' }}>
              {isMr ? 'सदस्यता नोंद:' : 'Joined:'} {profileData.joinDate || '01 Jan 2026'}
            </span>
          </div>
        </div>

        {/* Quick Territory Info Strip */}
        <div
          style={{
            background: '#f8fafc',
            padding: '14px 24px',
            borderTop: '1px solid #e2e8f0',
            borderBottom: '1px solid #e2e8f0',
            display: 'grid',
            gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))',
            gap: '12px',
            fontSize: '12.5px',
          }}
        >
          <div>
            <span style={{ color: '#64748b', fontSize: '11px', display: 'block' }}>{isMr ? 'विधानसभा मतदारसंघ' : 'Assembly Constituency'}</span>
            <span style={{ fontWeight: '750', color: '#0f172a' }}>🏛️ {profileData.assembly_name || profileData.assignedWard || (isMr ? 'विधानसभा मतदारसंघ' : 'Assembly Constituency')}</span>
          </div>
          <div>
            <span style={{ color: '#64748b', fontSize: '11px', display: 'block' }}>{isMr ? 'नियुक्त प्रभाग' : 'Assigned Ward'}</span>
            <span style={{ fontWeight: '750', color: '#0f172a' }}>📍 {profileData.assignedWard || (isMr ? 'प्रभाग क्र. १२' : 'Ward 12')}</span>
          </div>
          <div>
            <span style={{ color: '#64748b', fontSize: '11px', display: 'block' }}>{isMr ? 'प्राथमिक बूथ' : 'Primary Booths'}</span>
            <span style={{ fontWeight: '750', color: '#0f172a' }}>🗳️ {profileData.primaryBooths || (isMr ? 'बूथ क्र. १०१, १०२' : 'Booths 101, 102')}</span>
          </div>
          <div>
            <span style={{ color: '#64748b', fontSize: '11px', display: 'block' }}>{isMr ? 'मतदार ओळखपत्र क्र.' : 'Voter ID (EPIC)'}</span>
            <span style={{ fontWeight: '750', color: '#0f172a', fontFamily: 'monospace' }}>🪪 {profileData.epic || 'MH/44/082104'}</span>
          </div>
        </div>
      </div>

      {/* 4 FIELD PERFORMANCE & ACTIVITY METRICS */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: '14px' }}>
        <div style={{ background: '#ffffff', border: '1px solid #e2e8f0', borderTop: '3px solid #3b82f6', borderRadius: '12px', padding: '16px', boxShadow: '0 1px 4px rgba(0,0,0,0.03)' }}>
          <div style={{ fontSize: '12px', color: '#64748b', fontWeight: '700' }}>{isMr ? 'एकूण नियुक्त मतदार' : 'Assigned Voters'}</div>
          <div style={{ fontSize: '24px', fontWeight: '900', color: '#0f172a', marginTop: '4px' }}>{dashboardStats.totalAssigned?.toLocaleString() || 0}</div>
          <div style={{ fontSize: '11px', color: '#2563eb', fontWeight: '600', marginTop: '4px' }}>{dashboardStats.contactRate || 0}% {isMr ? 'संपर्क पूर्ण' : 'Done'}</div>
        </div>

        <div style={{ background: '#ffffff', border: '1px solid #e2e8f0', borderTop: '3px solid #10b981', borderRadius: '12px', padding: '16px', boxShadow: '0 1px 4px rgba(0,0,0,0.03)' }}>
          <div style={{ fontSize: '12px', color: '#64748b', fontWeight: '700' }}>{isMr ? 'थेट संपर्क साधलेले' : 'Contacted Voters'}</div>
          <div style={{ fontSize: '24px', fontWeight: '900', color: '#0f172a', marginTop: '4px' }}>{dashboardStats.contacted?.toLocaleString() || 0}</div>
          <div style={{ fontSize: '11px', color: '#16a34a', fontWeight: '600', marginTop: '4px' }}>{isMr ? 'थेट घराघरात संवाद' : 'Direct Visits'}</div>
        </div>

        <div style={{ background: '#ffffff', border: '1px solid #e2e8f0', borderTop: '3px solid #8b5cf6', borderRadius: '12px', padding: '16px', boxShadow: '0 1px 4px rgba(0,0,0,0.03)' }}>
          <div style={{ fontSize: '12px', color: '#64748b', fontWeight: '700' }}>{isMr ? 'पूर्ण केलेले सर्व्हे' : 'Surveys Completed'}</div>
          <div style={{ fontSize: '24px', fontWeight: '900', color: '#0f172a', marginTop: '4px' }}>{dashboardStats.surveysCompleted?.toLocaleString() || 0}</div>
          <div style={{ fontSize: '11px', color: '#7c3aed', fontWeight: '600', marginTop: '4px' }}>{isMr ? 'कुटुंब सर्वेक्षण नोंद' : 'Household Records'}</div>
        </div>

        <div style={{ background: '#ffffff', border: '1px solid #e2e8f0', borderTop: '3px solid #f97316', borderRadius: '12px', padding: '16px', boxShadow: '0 1px 4px rgba(0,0,0,0.03)' }}>
          <div style={{ fontSize: '12px', color: '#64748b', fontWeight: '700' }}>{isMr ? 'हजेरी दर्जा' : 'Attendance Score'}</div>
          <div style={{ fontSize: '24px', fontWeight: '900', color: '#0f172a', marginTop: '4px' }}>१००%</div>
          <div style={{ fontSize: '11px', color: '#ea580c', fontWeight: '600', marginTop: '4px' }}>{isMr ? 'नियमित व समयबद्ध' : 'Punctual & Regular'}</div>
        </div>
      </div>

      {/* 2-COLUMN STRUCTURED FORM & TERRITORY DETAILS */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(360px, 1fr))', gap: '20px' }}>
        {/* COLUMN 1: Personal & Contact Information */}
        <div className="karyakarta-card">
          <div className="karyakarta-card-header">
            <span className="karyakarta-card-title">
              <User size={18} color="#2563eb" />
              <span>{isMr ? 'वैयक्तिक व संपर्क तपशील' : 'Personal & Contact Information'}</span>
            </span>
            <span style={{ fontSize: '11px', color: '#64748b' }}>{isMr ? 'थेट माहिती' : 'Verified'}</span>
          </div>

          <div style={{ display: 'flex', flexDirection: 'column', gap: '14px' }}>
            <div>
              <label style={{ fontSize: '12px', fontWeight: '700', color: '#334155', display: 'block', marginBottom: '4px' }}>
                {t('fullName')} *
              </label>
              {isEditingProfile ? (
                <input
                  type="text"
                  name="fullName"
                  className="karyakarta-input"
                  value={profileData.fullName}
                  onChange={handleProfileChange}
                  placeholder={isMr ? 'पूर्ण नाव प्रविष्ट करा' : 'Enter full name'}
                />
              ) : (
                <div style={{ fontSize: '13.5px', fontWeight: '700', color: '#0f172a', padding: '6px 0' }}>
                  {profileData.fullName || '-'}
                </div>
              )}
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px' }}>
              <div>
                <label style={{ fontSize: '12px', fontWeight: '700', color: '#334155', display: 'block', marginBottom: '4px' }}>
                  {t('dob')}
                </label>
                {isEditingProfile ? (
                  <input
                    type="date"
                    name="dob"
                    className="karyakarta-input"
                    value={profileData.dob}
                    onChange={handleProfileChange}
                  />
                ) : (
                  <div style={{ fontSize: '13px', color: '#0f172a', padding: '6px 0' }}>
                    {profileData.dob ? new Date(profileData.dob).toLocaleDateString() : '-'}
                  </div>
                )}
              </div>

              <div>
                <label style={{ fontSize: '12px', fontWeight: '700', color: '#334155', display: 'block', marginBottom: '4px' }}>
                  {t('phoneNumber')} *
                </label>
                {isEditingProfile ? (
                  <input
                    type="text"
                    name="phone"
                    className="karyakarta-input"
                    value={profileData.phone}
                    onChange={handleProfileChange}
                    placeholder={isMr ? '१० अंकी मोबाइल' : 'Mobile number'}
                  />
                ) : (
                  <div style={{ fontSize: '13px', color: '#0f172a', padding: '6px 0', display: 'flex', alignItems: 'center', gap: '6px' }}>
                    <Phone size={14} color="#16a34a" />
                    <span style={{ fontWeight: '700' }}>{profileData.phone || '-'}</span>
                  </div>
                )}
              </div>
            </div>

            <div>
              <label style={{ fontSize: '12px', fontWeight: '700', color: '#334155', display: 'block', marginBottom: '4px' }}>
                {t('emailAddress')}
              </label>
              {isEditingProfile ? (
                <input
                  type="email"
                  name="email"
                  className="karyakarta-input"
                  value={profileData.email}
                  onChange={handleProfileChange}
                  placeholder={isMr ? 'ईमेल आयडी' : 'Email address'}
                />
              ) : (
                <div style={{ fontSize: '13px', color: '#0f172a', padding: '6px 0', display: 'flex', alignItems: 'center', gap: '6px' }}>
                  <Mail size={14} color="#2563eb" />
                  <span>{profileData.email || '-'}</span>
                </div>
              )}
            </div>

            <div>
              <label style={{ fontSize: '12px', fontWeight: '700', color: '#334155', display: 'block', marginBottom: '4px' }}>
                {t('residentialAddress')}
              </label>
              {isEditingProfile ? (
                <textarea
                  name="address"
                  className="karyakarta-input"
                  rows="2"
                  value={profileData.address}
                  onChange={handleProfileChange}
                  placeholder={isMr ? 'संपूर्ण निवासी पत्ता' : 'Residential address'}
                />
              ) : (
                <div style={{ fontSize: '13px', color: '#334155', padding: '6px 0', lineHeight: 1.4 }}>
                  {profileData.address || '-'}
                </div>
              )}
            </div>
          </div>
        </div>

        {/* COLUMN 2: Political & Booth Assignment */}
        <div className="karyakarta-card">
          <div className="karyakarta-card-header">
            <span className="karyakarta-card-title">
              <Shield size={18} color="#ea580c" />
              <span>{isMr ? 'राजकीय व निवडणूक कार्यक्षेत्र तपशील' : 'Political & Booth Assignment'}</span>
            </span>
            <span style={{ fontSize: '11px', background: '#fff7ed', color: '#ea580c', padding: '2px 8px', borderRadius: '10px', fontWeight: '700' }}>
              {isMr ? 'अ‍ॅडमिन मान्यताप्राप्त' : 'Admin Assigned'}
            </span>
          </div>

          <div style={{ display: 'flex', flexDirection: 'column', gap: '14px' }}>
            <div>
              <label style={{ fontSize: '12px', fontWeight: '700', color: '#334155', display: 'block', marginBottom: '4px' }}>
                {t('assignedWard')}
              </label>
              {isEditingProfile ? (
                <input
                  type="text"
                  name="assignedWard"
                  className="karyakarta-input"
                  value={profileData.assignedWard}
                  onChange={handleProfileChange}
                  placeholder={isMr ? 'उदा. प्रभाग क्र. १२' : 'Ward number'}
                />
              ) : (
                <div style={{ fontSize: '13.5px', fontWeight: '750', color: '#0f172a', padding: '6px 0' }}>
                  {profileData.assignedWard || (isMr ? 'प्रभाग क्र. १२' : 'Ward 12')}
                </div>
              )}
            </div>

            <div>
              <label style={{ fontSize: '12px', fontWeight: '700', color: '#334155', display: 'block', marginBottom: '4px' }}>
                {t('primaryBooths')}
              </label>
              {isEditingProfile ? (
                <input
                  type="text"
                  name="primaryBooths"
                  className="karyakarta-input"
                  value={profileData.primaryBooths}
                  onChange={handleProfileChange}
                  placeholder={isMr ? 'उदा. बूथ १०१, १०२' : 'Assigned booths'}
                />
              ) : (
                <div style={{ fontSize: '13px', color: '#0f172a', padding: '6px 0' }}>
                  {profileData.primaryBooths || (isMr ? 'बूथ क्र. १०१, बूथ क्र. १०२ (शाळा क्र. ४)' : 'Booths 101, 102')}
                </div>
              )}
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px' }}>
              <div>
                <label style={{ fontSize: '12px', fontWeight: '700', color: '#334155', display: 'block', marginBottom: '4px' }}>
                  {t('epic')}
                </label>
                {isEditingProfile ? (
                  <input
                    type="text"
                    name="epic"
                    className="karyakarta-input"
                    value={profileData.epic}
                    onChange={handleProfileChange}
                    placeholder={isMr ? 'मतदार ओळखपत्र क्र.' : 'Voter ID'}
                  />
                ) : (
                  <div style={{ fontSize: '13px', fontWeight: '700', color: '#0f172a', fontFamily: 'monospace', padding: '6px 0' }}>
                    {profileData.epic || 'MH/44/082104'}
                  </div>
                )}
              </div>

              <div>
                <label style={{ fontSize: '12px', fontWeight: '700', color: '#334155', display: 'block', marginBottom: '4px' }}>
                  {t('joinDate')}
                </label>
                {isEditingProfile ? (
                  <input
                    type="date"
                    name="joinDate"
                    className="karyakarta-input"
                    value={profileData.joinDate}
                    onChange={handleProfileChange}
                  />
                ) : (
                  <div style={{ fontSize: '13px', color: '#0f172a', padding: '6px 0' }}>
                    {profileData.joinDate ? new Date(profileData.joinDate).toLocaleDateString() : '-'}
                  </div>
                )}
              </div>
            </div>

            <div style={{ borderTop: '1px solid #f1f5f9', paddingTop: '12px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <button
                type="button"
                onClick={() => alert(isMr ? 'पासवर्ड बदलण्याची लिंक आपल्या अधिकृत मोबाइलवर पाठवली आहे.' : 'Password reset link sent to mobile.')}
                className="karyakarta-btn karyakarta-btn-outline"
                style={{ fontSize: '12px', padding: '6px 12px' }}
              >
                <Shield size={14} />
                <span>{t('changePassword')}</span>
              </button>

              <span style={{ fontSize: '11px', color: '#64748b' }}>
                🔒 {isMr ? 'सुरक्षित प्रमाणीकरण' : 'Secure Auth'}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
