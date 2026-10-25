import React, { useState } from 'react';
import {
  Clock,
  ShieldCheck,
  MapPin,
  RefreshCw,
  LogIn,
  LogOut,
  UserCheck,
  CheckCircle2,
  CalendarCheck,
  X,
  AlertTriangle,
  ChevronLeft,
  ChevronRight,
  Star,
  Eye,
  Calendar as CalendarIcon
} from 'lucide-react';
import Webcam from 'react-webcam';

export default function AttendanceTab({
  isMr,
  t,
  attendanceHistory = [],
  attendanceState,
  attendancePolicy,
  currentCoords,
  locationDetecting,
  detectCurrentLocation,
  startPunchIn,
  startPunchOut,
  isScanningFace,
  setIsScanningFace,
  punchMode,
  webcamRef,
  webcamError,
  setWebcamError,
  confirmPunch,
  LiveClock,
}) {
  const todayRecord = attendanceHistory && attendanceHistory.length > 0 ? attendanceHistory[0] : null;
  const hasPunchedIn = Boolean(todayRecord?.punch_in_time && todayRecord.punch_in_time !== '-');

  return (
    <div className="module-container" style={{ display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>
      {/* 1. TOP ATTENDANCE & LIVE HEADER */}
      <div
        style={{
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          flexWrap: 'wrap',
          gap: '16px',
          width: '100%',
          padding: '4px 2px',
        }}
      >
        <div style={{ display: 'flex', alignItems: 'center', gap: '14px' }}>
          <div
            style={{
              width: '52px',
              height: '52px',
              borderRadius: '16px',
              background: 'linear-gradient(135deg, #1e40af 0%, #3b82f6 100%)',
              color: '#ffffff',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              boxShadow: '0 8px 18px rgba(37, 99, 235, 0.25)',
              flexShrink: 0,
            }}
          >
            <CalendarCheck size={28} color="#ffffff" strokeWidth={2.2} />
          </div>
          <div>
            <div style={{ display: 'flex', alignItems: 'center', gap: '10px', flexWrap: 'wrap' }}>
              <h1 style={{ margin: 0, fontSize: '24px', fontWeight: '900', color: '#0f172a', letterSpacing: '-0.4px', lineHeight: 1.15 }}>
                {isMr ? 'डिजिटल उपस्थिती' : 'Attendance'}
              </h1>
              <span
                style={{
                  display: 'inline-flex',
                  alignItems: 'center',
                  gap: '6px',
                  padding: '4px 12px',
                  borderRadius: '20px',
                  background: '#ecfdf5',
                  border: '1.5px solid #a7f3d0',
                  color: '#059669',
                  fontSize: '12px',
                  fontWeight: '800',
                  boxShadow: '0 2px 6px rgba(5, 150, 105, 0.12)',
                  letterSpacing: '0.4px',
                }}
              >
                <span style={{ position: 'relative', display: 'flex', height: '8px', width: '8px' }}>
                  <span style={{ position: 'absolute', display: 'inline-flex', height: '100%', width: '100%', borderRadius: '50%', background: '#10b981', opacity: 0.75, animation: 'ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite' }} />
                  <span style={{ position: 'relative', display: 'inline-flex', borderRadius: '50%', height: '8px', width: '8px', background: '#059669' }} />
                </span>
                <span>{isMr ? 'लाईव्ह' : 'Live'}</span>
              </span>
            </div>
            <div style={{ fontSize: '12.5px', color: '#64748b', fontWeight: '600', marginTop: '3px' }}>
              {isMr ? 'थेट बायोमेट्रिक उपस्थिती कक्ष' : 'Live Biometric Attendance Desk'}
            </div>
          </div>
        </div>

        {/* Live IST Clock Card */}
        {LiveClock && (
          <div
            style={{
              display: 'flex',
              alignItems: 'center',
              gap: '12px',
              background: '#ffffff',
              border: '1.5px solid #e2e8f0',
              borderRadius: '16px',
              padding: '9px 18px',
              boxShadow: '0 2px 10px rgba(15, 23, 42, 0.05)',
              flexShrink: 0,
            }}
          >
            <div style={{ width: '40px', height: '40px', borderRadius: '12px', background: '#fff7ed', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#ea580c', flexShrink: 0, border: '1px solid #ffedd5' }}>
              <Clock size={20} strokeWidth={2.2} />
            </div>
            <LiveClock isMr={isMr} variant="card" />
          </div>
        )}
      </div>

      {/* Location Policy & GPS Status Strip */}
      <div
        className="attendance-location-policy-grid"
        style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 300px), 1fr))',
          gap: '12px',
          width: '100%',
          boxSizing: 'border-box',
        }}
      >
        {/* Admin Policy Notice */}
        <div
          style={{
            padding: '14px 16px',
            borderRadius: '16px',
            backgroundColor: attendancePolicy?.is_location_required ? '#ecfdf5' : '#f0f9ff',
            border: attendancePolicy?.is_location_required ? '1.5px solid #86efac' : '1.5px solid #bae6fd',
            color: attendancePolicy?.is_location_required ? '#065f46' : '#0369a1',
            fontSize: '13px',
            lineHeight: '1.5',
            display: 'flex',
            alignItems: 'flex-start',
            gap: '12px',
            boxShadow: '0 2px 10px rgba(0,0,0,0.03)',
            boxSizing: 'border-box',
            width: '100%',
            minWidth: 0,
          }}
        >
          {/* Prominent Red Location Pin Symbol */}
          <div
            style={{
              width: '42px',
              height: '42px',
              borderRadius: '12px',
              background: attendancePolicy?.is_location_required ? '#fee2e2' : '#e0f2fe',
              color: attendancePolicy?.is_location_required ? '#ef4444' : '#0284c7',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              flexShrink: 0,
              border: attendancePolicy?.is_location_required ? '1.5px solid #fca5a5' : '1.5px solid #bae6fd',
              boxShadow: '0 4px 10px rgba(239, 68, 68, 0.15)',
            }}
            title={isMr ? 'स्थान चिन्ह (Location Pin)' : 'Location Pin'}
          >
            <MapPin size={24} strokeWidth={2.4} />
          </div>

          <div style={{ flex: 1, minWidth: 0 }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '8px', flexWrap: 'wrap' }}>
              <strong style={{ fontSize: '13.5px', fontWeight: '850', color: attendancePolicy?.is_location_required ? '#065f46' : '#0369a1', lineHeight: 1.3 }}>
                {attendancePolicy?.is_location_required
                  ? (isMr ? 'अ‍ॅडमिन उपस्थिती धोरण (अनिवार्य स्थान)' : 'Admin Designated Location Enforced')
                  : (isMr ? 'लवचिक उपस्थिती धोरण' : 'Flexible Attendance Policy')}
              </strong>
            </div>

            <div style={{ marginTop: '5px', fontSize: '12.5px', fontWeight: '600', color: attendancePolicy?.is_location_required ? '#166534' : '#0284c7', display: 'flex', alignItems: 'flex-start', gap: '6px' }}>
              <span style={{ color: '#ef4444', fontSize: '14px', flexShrink: 0, marginTop: '-1px' }}>📍</span>
              <span style={{ wordBreak: 'break-word', overflowWrap: 'anywhere', lineHeight: 1.4 }}>
                {attendancePolicy?.is_location_required
                  ? (
                    <>
                      <strong style={{ color: '#0f172a' }}>{attendancePolicy.location_name || 'Shambhara Footi Road मध्यवर्ती कार्यालय (Live HQ)'}</strong>
                      {' '}
                      <span style={{ opacity: 0.9 }}>
                        ({attendancePolicy.address || 'Shambhara Footi Road, Viya Nagar, सांगली, मिरज, सांगली जिल्हा, महाराष्ट्र, 416416, भारत'})
                      </span>
                      {' • '}
                      <span style={{ fontWeight: '800', color: '#047857', display: 'inline-block' }}>
                        {isMr ? `जिओफेन्स मर्यादा: ${attendancePolicy.radius_meters || 500} मीटर` : `Geofence Limit: ${attendancePolicy.radius_meters || 500}m`}
                      </span>
                    </>
                  )
                  : (isMr
                    ? 'आपण कार्यक्षेत्रातून कोणत्याही ठिकाणाहून हजेरी नोंदवू शकता (Anywhere Attendance Allowed).'
                    : 'You can punch attendance from any field location.')}
              </span>
            </div>
          </div>
        </div>

        {/* Current Detected Coordinates Strip */}
        <div
          style={{
            padding: '14px 16px',
            borderRadius: '16px',
            backgroundColor: '#ffffff',
            border: '1.5px solid #e2e8f0',
            fontSize: '12.5px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            flexWrap: 'wrap',
            gap: '12px',
            boxShadow: '0 2px 10px rgba(0,0,0,0.03)',
            boxSizing: 'border-box',
            width: '100%',
            minWidth: 0,
          }}
        >
          <div style={{ display: 'flex', alignItems: 'flex-start', gap: '12px', flex: 1, minWidth: 0 }}>
            <div
              style={{
                width: '42px',
                height: '42px',
                borderRadius: '12px',
                background: '#eff6ff',
                color: '#2563eb',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                flexShrink: 0,
                border: '1.5px solid #bfdbfe',
                boxShadow: '0 4px 10px rgba(37, 99, 235, 0.12)',
              }}
              title="GPS Location Pin"
            >
              <MapPin size={24} strokeWidth={2.4} />
            </div>

            <div style={{ flex: 1, minWidth: 0 }}>
              <div style={{ fontSize: '11px', color: '#64748b', fontWeight: '800', textTransform: 'uppercase', letterSpacing: '0.5px', display: 'flex', alignItems: 'center', gap: '4px' }}>
                <span>📍</span>
                <span>{isMr ? 'सध्याचे GPS स्थान (Current Location)' : 'Current GPS Location'}</span>
              </div>
              <div
                style={{
                  fontWeight: '750',
                  color: '#0f172a',
                  wordBreak: 'break-word',
                  overflowWrap: 'anywhere',
                  lineHeight: '1.4',
                  marginTop: '2px',
                  fontSize: '13px',
                  width: '100%'
                }}
                title={currentCoords?.address || 'स्थान मिळवत आहे...'}
              >
                {locationDetecting
                  ? (isMr ? '🔄 GPS स्थान शोधत आहे...' : 'Detecting GPS...')
                  : currentCoords?.address
                    ? currentCoords.address
                    : currentCoords?.latitude
                      ? `Lat: ${currentCoords.latitude}, Lng: ${currentCoords.longitude}`
                      : (isMr ? 'स्थान उपलब्ध नाही (रिफ्रेश GPS वर क्लिक करा)' : 'GPS not detected yet')}
              </div>
            </div>
          </div>

          <button
            type="button"
            className="karyakarta-btn karyakarta-btn-secondary"
            onClick={detectCurrentLocation}
            disabled={locationDetecting}
            title={isMr ? 'रिफ्रेश स्थान' : 'Refresh GPS'}
            style={{
              padding: '8px 14px',
              fontSize: '12px',
              whiteSpace: 'nowrap',
              borderRadius: '12px',
              fontWeight: '750',
              flexShrink: 0,
              marginLeft: 'auto',
            }}
          >
            <RefreshCw size={13} className={locationDetecting ? 'animate-spin' : ''} />
            <span>{isMr ? 'रिफ्रेश GPS' : 'Refresh GPS'}</span>
          </button>
        </div>
      </div>

      {/* PROGRESSIVE PUNCH DESK: SHOWS PUNCH IN, AND AFTER PUNCH IN SHOWS PUNCH OUT THERE */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 300px), 1fr))', gap: '16px' }}>
        {/* MAIN PROGRESSIVE ACTION CARD */}
        <div
          className={`attendance-punch-card ${!hasPunchedIn ? 'in-card' : attendanceState === 'Completed' ? '' : 'out-card'}`}
          style={{ minHeight: '320px' }}
        >
          {!hasPunchedIn ? (
            /* STATE 1: NOT PUNCHED IN YET -> SHOW PUNCH IN */
            <>
              <div>
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '14px' }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                    <div style={{ width: '44px', height: '44px', borderRadius: '12px', background: '#dcfce7', color: '#16a34a', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                      <LogIn size={22} />
                    </div>
                    <div>
                      <h3 style={{ fontSize: '17px', fontWeight: '800', color: '#0f172a', margin: 0 }}>
                        {isMr ? 'सकाळची हजेरी (Morning Punch In)' : 'Morning Punch In'}
                      </h3>
                      <span style={{ fontSize: '12px', color: '#16a34a', fontWeight: '600' }}>
                        {isMr ? 'ड्युटी प्रारंभ • उपस्थिती नोंदवा' : 'Start Shift • Check In'}
                      </span>
                    </div>
                  </div>

                  <span className="karyakarta-badge karyakarta-badge-amber">
                    {isMr ? '⚪ हजेरी बाकी' : 'Pending'}
                  </span>
                </div>

                <p style={{ fontSize: '13px', color: '#475569', lineHeight: '1.6', margin: '0 0 16px 0' }}>
                  {isMr
                    ? 'कामावर रुजू होताना सेल्फी फोटो काढून आणि चालू GPS स्थान निश्चित करून पंच इन नोंदवा.'
                    : 'Start your shift by capturing a live selfie photo and verifying your GPS location.'}
                </p>

                <div style={{ background: '#ffffff', border: '1px dashed #cbd5e1', borderRadius: '10px', padding: '12px 14px', fontSize: '12px', color: '#64748b', display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '16px' }}>
                  <MapPin size={18} color="#2563eb" style={{ flexShrink: 0 }} />
                  <div style={{ wordBreak: 'break-word', overflowWrap: 'anywhere', lineHeight: 1.4, flex: 1, minWidth: 0 }}>
                    {currentCoords?.address || (isMr ? 'चालू GPS स्थान आपोआप जोडले जाईल' : 'Live GPS coordinates will be captured')}
                  </div>
                </div>
              </div>

              <div>
                <button
                  type="button"
                  className="punch-btn-in"
                  onClick={startPunchIn}
                >
                  <LogIn size={18} />
                  <span>{isMr ? '🟢 Punch In करा (सकाळची हजेरी)' : 'Punch In (Start Shift)'}</span>
                </button>
                <div style={{ fontSize: '11px', color: '#64748b', textAlign: 'center', marginTop: '6px' }}>
                  {isMr ? '📷 कॅमेरा सेल्फी + GPS स्थान तपासणी' : 'Live Camera Selfie + Geotag'}
                </div>
              </div>
            </>
          ) : attendanceState !== 'Completed' ? (
            /* STATE 2: ALREADY PUNCHED IN -> SHOW PUNCH OUT RIGHT THERE! */
            <>
              <div>
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '14px' }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                    <div style={{ width: '44px', height: '44px', borderRadius: '12px', background: '#ffedd5', color: '#ea580c', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                      <LogOut size={22} />
                    </div>
                    <div>
                      <h3 style={{ fontSize: '17px', fontWeight: '800', color: '#0f172a', margin: 0 }}>
                        {isMr ? 'संध्याकाळचे निर्गमन (Evening Punch Out)' : 'Evening Punch Out'}
                      </h3>
                      <span style={{ fontSize: '12px', color: '#ea580c', fontWeight: '600' }}>
                        {isMr ? 'ड्युटी समाप्ती • निर्गमन नोंदवा' : 'End Shift • Check Out'}
                      </span>
                    </div>
                  </div>

                  <span className="karyakarta-badge karyakarta-badge-live">
                    {isMr ? '🟢 ड्युटी सुरू (On Duty)' : 'On Duty'}
                  </span>
                </div>

                {/* Today's Punch In Confirmation Strip */}
                <div style={{ background: '#ffffff', border: '1px solid #bbf7d0', borderRadius: '12px', padding: '12px 14px', marginBottom: '16px', display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '12px' }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                    {todayRecord?.punch_in_photo ? (
                      <img
                        src={todayRecord.punch_in_photo}
                        alt="Punch In Selfie"
                        style={{ width: '42px', height: '42px', borderRadius: '8px', objectFit: 'cover', border: '1.5px solid #86efac' }}
                      />
                    ) : (
                      <div style={{ width: '42px', height: '42px', borderRadius: '8px', background: '#dcfce7', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#16a34a' }}>
                        <UserCheck size={20} />
                      </div>
                    )}
                    <div>
                      <div style={{ fontSize: '11px', color: '#059669', fontWeight: '700' }}>
                        {isMr ? '✓ सकाळची हजेरी नोंद झाली' : 'Punched In Successfully'}
                      </div>
                      <div style={{ fontSize: '15px', fontWeight: '900', color: '#0f172a' }}>
                        ⏰ {todayRecord?.punch_in_time}
                      </div>
                    </div>
                  </div>

                  <button
                    type="button"
                    onClick={startPunchIn}
                    style={{ background: 'transparent', border: 'none', color: '#059669', fontSize: '11.5px', fontWeight: '700', cursor: 'pointer', textDecoration: 'underline' }}
                    title="पुन्हा Punch In अपडेट करा"
                  >
                    {isMr ? 'अपडेट' : 'Update'}
                  </button>
                </div>

                <p style={{ fontSize: '13px', color: '#475569', lineHeight: '1.6', margin: '0 0 16px 0' }}>
                  {isMr
                    ? 'आपण सध्या ड्युटीवर आहात. दिवसाचे क्षेत्रीय कार्य संपल्यावर आता खालील बटनावर क्लिक करून Punch Out नोंदवा.'
                    : 'Your shift is active. When you finish work today, click below to log your exit punch out.'}
                </p>
              </div>

              <div>
                <button
                  type="button"
                  className="punch-btn-out"
                  onClick={startPunchOut}
                >
                  <LogOut size={18} />
                  <span>{isMr ? '🟠 Punch Out करा (संध्याकाळचे निर्गमन)' : 'Punch Out (End Shift)'}</span>
                </button>
                <div style={{ fontSize: '11px', color: '#64748b', textAlign: 'center', marginTop: '6px' }}>
                  {isMr ? '⏱️ सेल्फी फोटो व निर्गमन वेळेसह कर्तव्य समाप्त' : 'Duty completion time & exit photo'}
                </div>
              </div>
            </>
          ) : (
            /* STATE 3: SHIFT COMPLETED -> BOTH RECORDED */
            <>
              <div>
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '14px' }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                    <div style={{ width: '44px', height: '44px', borderRadius: '12px', background: '#dcfce7', color: '#16a34a', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                      <CheckCircle2 size={24} />
                    </div>
                    <div>
                      <h3 style={{ fontSize: '17px', fontWeight: '800', color: '#0f172a', margin: 0 }}>
                        {isMr ? 'आजचे कर्तव्य पूर्ण (Shift Completed)' : "Today's Shift Completed"}
                      </h3>
                      <span style={{ fontSize: '12px', color: '#16a34a', fontWeight: '600' }}>
                        {isMr ? 'हजेरी व निर्गमन दोन्ही नोंदवले' : 'Punch In & Punch Out Logged'}
                      </span>
                    </div>
                  </div>

                  <span className="karyakarta-badge karyakarta-badge-live">
                    {isMr ? '✓ पूर्ण झाले' : 'Completed'}
                  </span>
                </div>

                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px', marginBottom: '16px' }}>
                  <div style={{ background: '#f0fdf4', border: '1px solid #bbf7d0', borderRadius: '10px', padding: '10px 12px', display: 'flex', alignItems: 'center', gap: '10px' }}>
                    {todayRecord?.punch_in_photo && (
                      <img src={todayRecord.punch_in_photo} alt="Punch In" style={{ width: '38px', height: '38px', borderRadius: '6px', objectFit: 'cover' }} />
                    )}
                    <div>
                      <span style={{ fontSize: '10.5px', color: '#16a34a', fontWeight: '700' }}>{isMr ? 'पंच इन' : 'Punch In'}</span>
                      <div style={{ fontSize: '14px', fontWeight: '900', color: '#0f172a' }}>{todayRecord?.punch_in_time}</div>
                    </div>
                  </div>

                  <div style={{ background: '#fff7ed', border: '1px solid #fed7aa', borderRadius: '10px', padding: '10px 12px', display: 'flex', alignItems: 'center', gap: '10px' }}>
                    {todayRecord?.punch_out_photo && (
                      <img src={todayRecord.punch_out_photo} alt="Punch Out" style={{ width: '38px', height: '38px', borderRadius: '6px', objectFit: 'cover' }} />
                    )}
                    <div>
                      <span style={{ fontSize: '10.5px', color: '#ea580c', fontWeight: '700' }}>{isMr ? 'पंच आउट' : 'Punch Out'}</span>
                      <div style={{ fontSize: '14px', fontWeight: '900', color: '#0f172a' }}>{todayRecord?.punch_out_time}</div>
                    </div>
                  </div>
                </div>

                <p style={{ fontSize: '13px', color: '#475569', lineHeight: '1.6', margin: '0 0 16px 0' }}>
                  {isMr
                    ? 'आजचे दैनिक क्षेत्रीय कार्य यशस्वीरीत्या पूर्ण झाले आहे. धन्यवाद!'
                    : 'Your daily field operations have been logged successfully for today. Thank you!'}
                </p>
              </div>

              <div style={{ display: 'flex', gap: '10px' }}>
                <button
                  type="button"
                  className="karyakarta-btn karyakarta-btn-secondary"
                  onClick={startPunchOut}
                  style={{ width: '100%', padding: '10px', fontSize: '12px' }}
                >
                  <RefreshCw size={14} />
                  <span>{isMr ? 'पुन्हा Punch Out अपडेट करा' : 'Re-Punch Out'}</span>
                </button>
              </div>
            </>
          )}
        </div>

        {/* CARD 2: DUTY STATUS & TIMELINE SUMMARY */}
        <div
          style={{
            background: '#ffffff',
            border: '1px solid #e2e8f0',
            borderRadius: '16px',
            padding: '24px',
            display: 'flex',
            flexDirection: 'column',
            justifyContent: 'space-between',
            boxShadow: '0 2px 8px rgba(15, 23, 42, 0.03)',
          }}
        >
          <div>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '16px' }}>
              <h3 style={{ fontSize: '15px', fontWeight: '800', color: '#0f172a', margin: 0 }}>
                {isMr ? 'आजची उपस्थिती स्थिती' : "Today's Shift Status"}
              </h3>
              <span
                className={`badge-status ${attendanceState === 'Punched In'
                  ? 'status-progress'
                  : attendanceState === 'Completed'
                    ? 'status-completed'
                    : 'status-pending'
                  }`}
                style={{ fontSize: '12px', padding: '4px 10px' }}
              >
                {attendanceState === 'Punched In'
                  ? (isMr ? '🟢 कामावर हजर (On Duty)' : 'On Duty')
                  : attendanceState === 'Completed'
                    ? (isMr ? '✓ कर्तव्य पूर्ण (Completed)' : 'Completed')
                    : (isMr ? '⚪ उपस्थिती बाकी (Not Started)' : 'Not Started')}
              </span>
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px', marginBottom: '16px' }}>
              <div style={{ background: '#f8fafc', padding: '12px', borderRadius: '10px', border: '1px solid #f1f5f9' }}>
                <div style={{ fontSize: '11px', color: '#64748b', fontWeight: '700' }}>
                  {isMr ? 'पंच इन वेळ' : 'Punch In'}
                </div>
                <div style={{ fontSize: '15px', fontWeight: '800', color: '#0f172a', marginTop: '2px' }}>
                  {todayRecord?.punch_in_time || '--:--'}
                </div>
              </div>

              <div style={{ background: '#f8fafc', padding: '12px', borderRadius: '10px', border: '1px solid #f1f5f9' }}>
                <div style={{ fontSize: '11px', color: '#64748b', fontWeight: '700' }}>
                  {isMr ? 'पंच आउट वेळ' : 'Punch Out'}
                </div>
                <div style={{ fontSize: '15px', fontWeight: '800', color: '#0f172a', marginTop: '2px' }}>
                  {todayRecord?.punch_out_time && todayRecord.punch_out_time !== '-' ? todayRecord.punch_out_time : '--:--'}
                </div>
              </div>
            </div>

            <div style={{ fontSize: '12.5px', color: '#475569', lineHeight: '1.5' }}>
              {attendanceState === 'Completed'
                ? (isMr ? '🎉 आजचे कर्तव्य यशस्वीरीत्या पूर्ण झाले आहे. धन्यवाद!' : 'Shift completed successfully today. Thank you!')
                : attendanceState === 'Punched In'
                  ? (isMr ? '👍 आपण सध्या ड्युटीवर आहात. कार्य संपल्यावर वरील Punch Out बटनावर क्लिक करा.' : 'You are currently on duty. Use the Punch Out button above when leaving.')
                  : (isMr ? '📌 कृपया दिवसाचे काम सुरू करण्यासाठी प्रथम Punch In करा.' : 'Please punch in first to begin today\'s shift.')}
            </div>
          </div>

          <div style={{ marginTop: '16px', paddingTop: '14px', borderTop: '1px solid #f1f5f9', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <span style={{ fontSize: '11.5px', color: '#64748b' }}>
              {isMr ? 'हजेरी धोरण:' : 'Policy:'} <strong>{attendancePolicy?.is_location_required ? 'स्थान आवश्यक' : 'सर्वत्र मान्य'}</strong>
            </span>
            <span style={{ fontSize: '11.5px', color: '#16a34a', fontWeight: '700' }}>
              {isMr ? '१००% अचूकता' : '100% Accurate'}
            </span>
          </div>
        </div>
      </div>

      {/* ATTENDANCE CAMERA & GEOLOCATION MODAL */}
      {isScanningFace && (
        <div className="attendance-modal-overlay">
          <div className="attendance-modal-content">
            {/* Modal Header */}
            <div
              style={{
                padding: '16px 20px',
                borderBottom: '1px solid #e2e8f0',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                background: punchMode === 'IN' ? 'linear-gradient(135deg, #ecfdf5 0%, #ffffff 100%)' : 'linear-gradient(135deg, #fff7ed 0%, #ffffff 100%)',
              }}
            >
              <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                <div
                  style={{
                    width: '34px',
                    height: '34px',
                    borderRadius: '10px',
                    background: punchMode === 'IN' ? '#10b981' : '#f97316',
                    color: '#ffffff',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                  }}
                >
                  {punchMode === 'IN' ? <LogIn size={18} /> : <LogOut size={18} />}
                </div>
                <div>
                  <h3 style={{ margin: 0, fontSize: '15px', fontWeight: '800', color: '#0f172a' }}>
                    {punchMode === 'IN'
                      ? (isMr ? 'पंच इन पडताळणी (Punch In Verification)' : 'Punch In Verification')
                      : (isMr ? 'पंच आउट पडताळणी (Punch Out Verification)' : 'Punch Out Verification')}
                  </h3>
                  <span style={{ fontSize: '11.5px', color: '#64748b' }}>
                    {isMr ? 'सेल्फी चेहरा स्कॅन व GPS स्थान खात्री' : 'Live Face Scan & GPS Verification'}
                  </span>
                </div>
              </div>

              <button
                type="button"
                onClick={() => setIsScanningFace(false)}
                style={{ background: 'none', border: 'none', cursor: 'pointer', color: '#64748b', padding: '4px' }}
              >
                <X size={20} />
              </button>
            </div>

            {/* Modal Body / Camera Viewport */}
            <div style={{ padding: '24px', display: 'flex', flexDirection: 'column', alignItems: 'center', gap: '18px' }}>
              <div
                style={{
                  position: 'relative',
                  width: '240px',
                  height: '240px',
                  borderRadius: '50%',
                  overflow: 'hidden',
                  border: `6px solid ${punchMode === 'IN' ? '#10b981' : '#f97316'}`,
                  boxShadow: `0 8px 24px ${punchMode === 'IN' ? 'rgba(16, 185, 129, 0.25)' : 'rgba(249, 115, 22, 0.25)'}`,
                  background: '#0f172a',
                }}
              >
                <Webcam
                  ref={webcamRef}
                  audio={false}
                  screenshotFormat="image/jpeg"
                  width="100%"
                  height="100%"
                  onUserMediaError={() => setWebcamError(true)}
                  style={{ objectFit: 'cover' }}
                />

                {webcamError && (
                  <div
                    style={{
                      position: 'absolute',
                      inset: 0,
                      background: 'rgba(15, 23, 42, 0.9)',
                      color: '#ffffff',
                      display: 'flex',
                      flexDirection: 'column',
                      alignItems: 'center',
                      justifyContent: 'center',
                      textAlign: 'center',
                      padding: '16px',
                      fontSize: '12px',
                      gap: '8px',
                    }}
                  >
                    <AlertTriangle size={24} color="#f87171" />
                    <span>कॅमेरा परवानगी आवश्यक आहे किंवा उपलब्ध नाही. ब्राउझरमध्ये कॅमेरा अनुमती द्या.</span>
                  </div>
                )}

                {/* Scanning Line Overlay */}
                <div
                  style={{
                    position: 'absolute',
                    top: 0,
                    left: 0,
                    width: '100%',
                    height: '100%',
                    background: `linear-gradient(to bottom, transparent 0%, ${punchMode === 'IN' ? 'rgba(16, 185, 129, 0.35)' : 'rgba(249, 115, 22, 0.35)'} 50%, transparent 100%)`,
                    animation: 'scanLine 1.8s infinite linear',
                    zIndex: 10,
                    pointerEvents: 'none',
                  }}
                />
              </div>

              {/* Geolocation & IST timestamp info box */}
              <div
                style={{
                  width: '100%',
                  background: '#f8fafc',
                  border: '1px solid #e2e8f0',
                  borderRadius: '12px',
                  padding: '12px 14px',
                  fontSize: '12px',
                  display: 'flex',
                  flexDirection: 'column',
                  gap: '6px',
                }}
              >
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                  <span style={{ color: '#64748b', fontWeight: '700' }}>⏰ {isMr ? 'वेळ व तारीख:' : 'Time & Date:'}</span>
                  {LiveClock ? <LiveClock isMr={isMr} variant="inline" /> : null}
                </div>

                <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: '10px' }}>
                  <span style={{ color: '#64748b', fontWeight: '700', flexShrink: 0 }}>📍 {isMr ? 'GPS स्थान:' : 'GPS Location:'}</span>
                  <span style={{ fontWeight: '700', color: '#0f172a', textAlign: 'right', wordBreak: 'break-word' }}>
                    {currentCoords?.address || (currentCoords?.latitude ? `${currentCoords.latitude}, ${currentCoords.longitude}` : (isMr ? 'स्थान तपासत आहे...' : 'Detecting...'))}
                  </span>
                </div>

                {attendancePolicy?.is_location_required && (
                  <div style={{ fontSize: '11px', color: '#059669', fontWeight: '600', marginTop: '2px' }}>
                    ✓ अ‍ॅडमिन उपस्थिती स्थान: {attendancePolicy.location_name || (isMr ? 'मध्यवर्ती नियंत्रण कार्यालय' : 'Command HQ')}
                  </div>
                )}
              </div>

              {/* Modal Action Buttons */}
              <div style={{ display: 'flex', gap: '12px', width: '100%' }}>
                <button
                  type="button"
                  className="karyakarta-btn karyakarta-btn-secondary"
                  onClick={() => setIsScanningFace(false)}
                  style={{ flex: 1, padding: '12px' }}
                >
                  {t('cancel')}
                </button>

                <button
                  type="button"
                  onClick={confirmPunch}
                  style={{
                    flex: 2,
                    padding: '12px 20px',
                    fontSize: '14.5px',
                    fontWeight: '800',
                    backgroundColor: punchMode === 'IN' ? '#10b981' : '#f97316',
                    color: '#ffffff',
                    border: 'none',
                    borderRadius: '10px',
                    cursor: 'pointer',
                    boxShadow: `0 4px 14px ${punchMode === 'IN' ? 'rgba(16, 185, 129, 0.35)' : 'rgba(249, 115, 22, 0.35)'}`,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    gap: '8px',
                  }}
                >
                  {punchMode === 'IN' ? <LogIn size={18} /> : <LogOut size={18} />}
                  <span>
                    {punchMode === 'IN'
                      ? (isMr ? '✓ Punch In निश्चित करा' : 'Confirm Punch In')
                      : (isMr ? '✓ Punch Out निश्चित करा' : 'Confirm Punch Out')}
                  </span>
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* ATTENDANCE CALENDAR CARD (उपस्थिती दिनदर्शिका) - MATCHING MOCKUP DESIGN */}
      <AttendanceCalendarDesk
        isMr={isMr}
        attendanceHistory={attendanceHistory}
        todayRecord={todayRecord}
      />
    </div>
  );
}

// ATTENDANCE CALENDAR DESK COMPONENT
function AttendanceCalendarDesk({ isMr, attendanceHistory = [], todayRecord }) {
  const [calMonth, setCalMonth] = useState(9); // 0-indexed: 9 = October
  const [calYear, setCalYear] = useState(2026);
  const [selectedDay, setSelectedDay] = useState(17);
  const [showFullTable, setShowFullTable] = useState(false);

  const monthNamesEn = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  const monthNamesMr = ['जाने', 'फेब्रु', 'मार्च', 'एप्रिल', 'मे', 'जून', 'जुलै', 'ऑगस्ट', 'सप्टें', 'ऑक्टो', 'नोव्हें', 'डिसें'];

  const prevMonth = () => {
    if (calMonth === 0) {
      setCalMonth(11);
      setCalYear((y) => y - 1);
    } else {
      setCalMonth((m) => m - 1);
    }
  };

  const nextMonth = () => {
    if (calMonth === 11) {
      setCalMonth(0);
      setCalYear((y) => y + 1);
    } else {
      setCalMonth((m) => m + 1);
    }
  };

  // Day statuses for October 2026 (matching mockup)
  const getDayStatus = (day) => {
    if (calMonth === 9 && calYear === 2026) {
      if (day === 17) return { type: 'today', label: isMr ? 'आज' : 'Today', inTime: todayRecord?.punch_in_time || '09:15 AM', outTime: todayRecord?.punch_out_time || '06:30 PM', note: isMr ? 'सक्रिय उपस्थिती' : 'Active Duty' };
      if (day === 2) return { type: 'holiday', icon: '★', label: isMr ? 'गांधी जयंती' : 'Gandhi Jayanti', note: isMr ? 'सार्वजनिक सुट्टी' : 'Public Holiday' };
      if (day === 4 || day === 11 || day === 18 || day === 25) return { type: 'sunday', label: isMr ? 'रविवार सुट्टी' : 'Sunday Off', note: isMr ? 'साप्ताहिक सुट्टी' : 'Weekly Off' };
      if (day === 6) return { type: 'leave', label: isMr ? 'रजा' : 'Leave', note: isMr ? 'अधिकृत रजा' : 'Approved Leave' };
      if (day === 10) return { type: 'incomplete', label: isMr ? 'अपूर्ण' : 'Half Day', inTime: '09:30 AM', outTime: '01:30 PM', note: isMr ? 'अर्धा दिवस' : 'Half Day Shift' };
      if (day >= 1 && day < 17) return { type: 'present', label: isMr ? 'हजर' : 'Present', inTime: '09:10 AM', outTime: '06:45 PM', note: isMr ? 'पूर्ण उपस्थिती' : 'Full Day Duty' };
      return { type: 'future', label: isMr ? 'नियोजित' : 'Upcoming' };
    }
    const dayOfWeek = new Date(calYear, calMonth, day).getDay();
    if (dayOfWeek === 0) return { type: 'sunday', label: isMr ? 'रविवार' : 'Sunday' };
    if (day < 17) return { type: 'present', label: isMr ? 'हजर' : 'Present', inTime: '09:15 AM', outTime: '06:30 PM' };
    return { type: 'future', label: isMr ? 'नियोजित' : 'Upcoming' };
  };

  const daysInMonth = new Date(calYear, calMonth + 1, 0).getDate();
  const firstDayOffset = new Date(calYear, calMonth, 1).getDay(); // 0 = Sun ... 4 = Thu for Oct 2026

  const selectedDayInfo = getDayStatus(selectedDay);

  return (
    <div
      style={{
        background: '#ffffff',
        borderRadius: '26px',
        border: '1.5px solid #e2e8f0',
        padding: '24px',
        boxShadow: '0 4px 20px rgba(15, 23, 42, 0.04)',
        display: 'flex',
        flexDirection: 'column',
        gap: '20px'
      }}
    >
      {/* 1. Header with Title, Subtitle and Month Navigation Pill */}
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '14px' }}>
        <div>
          <h2 style={{ margin: 0, fontSize: '19px', fontWeight: '900', color: '#0f172a', letterSpacing: '-0.3px' }}>
            {isMr ? 'उपस्थिती दिनदर्शिका' : 'Attendance Calendar'} <span style={{ fontSize: '15px', color: '#64748b', fontWeight: '750' }}>(CALENDAR)</span>
          </h2>
          <div style={{ fontSize: '13px', color: '#64748b', fontWeight: '600', marginTop: '3px' }}>
            Monthly attendance & duty overview
          </div>
        </div>

        {/* Month Selector Pill */}
        <div
          style={{
            background: '#ffffff',
            border: '1.5px solid #e2e8f0',
            borderRadius: '18px',
            padding: '6px 14px',
            display: 'flex',
            alignItems: 'center',
            gap: '14px',
            boxShadow: '0 2px 8px rgba(15, 23, 42, 0.03)'
          }}
        >
          <button
            type="button"
            onClick={prevMonth}
            style={{
              background: 'transparent',
              border: 'none',
              cursor: 'pointer',
              color: '#334155',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              padding: '4px',
              borderRadius: '8px'
            }}
            title={isMr ? 'मागील महिना' : 'Previous Month'}
          >
            <ChevronLeft size={18} strokeWidth={2.4} />
          </button>

          <div style={{ textAlign: 'center', minWidth: '60px' }}>
            <div style={{ fontSize: '14px', fontWeight: '900', color: '#0f172a', lineHeight: 1.1 }}>
              {isMr ? monthNamesMr[calMonth] : monthNamesEn[calMonth]}
            </div>
            <div style={{ fontSize: '12px', fontWeight: '800', color: '#0f172a', marginTop: '1px' }}>
              {calYear}
            </div>
          </div>

          <button
            type="button"
            onClick={nextMonth}
            style={{
              background: 'transparent',
              border: 'none',
              cursor: 'pointer',
              color: '#334155',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              padding: '4px',
              borderRadius: '8px'
            }}
            title={isMr ? 'पुढील महिना' : 'Next Month'}
          >
            <ChevronRight size={18} strokeWidth={2.4} />
          </button>
        </div>
      </div>

      {/* 2. Four Summary Status Count Pills (Matching mockup) */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(130px, 1fr))', gap: '12px' }}>
        {/* Pill 1: उपस्थित (Green) */}
        <div
          style={{
            background: '#ecfdf5',
            border: '1.5px solid #a7f3d0',
            borderRadius: '16px',
            padding: '10px 12px',
            textAlign: 'center'
          }}
        >
          <div style={{ fontSize: '20px', fontWeight: '900', color: '#059669', lineHeight: 1.1 }}>
            12
          </div>
          <div style={{ fontSize: '12px', fontWeight: '750', color: '#059669', marginTop: '3px' }}>
            {isMr ? 'उपस्थित' : 'Present'}
          </div>
        </div>

        {/* Pill 2: अपूर्ण (Amber) */}
        <div
          style={{
            background: '#fffdf5',
            border: '1.5px solid #fde68a',
            borderRadius: '16px',
            padding: '10px 12px',
            textAlign: 'center'
          }}
        >
          <div style={{ fontSize: '20px', fontWeight: '900', color: '#d97706', lineHeight: 1.1 }}>
            01
          </div>
          <div style={{ fontSize: '12px', fontWeight: '750', color: '#d97706', marginTop: '3px' }}>
            {isMr ? 'अपूर्ण' : 'Incomplete'}
          </div>
        </div>

        {/* Pill 3: रजा (Red) */}
        <div
          style={{
            background: '#fef2f2',
            border: '1.5px solid #fecaca',
            borderRadius: '16px',
            padding: '10px 12px',
            textAlign: 'center'
          }}
        >
          <div style={{ fontSize: '20px', fontWeight: '900', color: '#dc2626', lineHeight: 1.1 }}>
            01
          </div>
          <div style={{ fontSize: '12px', fontWeight: '750', color: '#dc2626', marginTop: '3px' }}>
            {isMr ? 'रजा' : 'Leave'}
          </div>
        </div>

        {/* Pill 4: सुट्टी (Gray) */}
        <div
          style={{
            background: '#f8fafc',
            border: '1.5px solid #e2e8f0',
            borderRadius: '16px',
            padding: '10px 12px',
            textAlign: 'center'
          }}
        >
          <div style={{ fontSize: '20px', fontWeight: '900', color: '#1e293b', lineHeight: 1.1 }}>
            04
          </div>
          <div style={{ fontSize: '12px', fontWeight: '750', color: '#64748b', marginTop: '3px' }}>
            {isMr ? 'सुट्टी' : 'Holiday'}
          </div>
        </div>
      </div>

      {/* 3. Monthly Calendar Grid Card */}
      <div
        style={{
          border: '1.5px solid #f1f5f9',
          borderRadius: '24px',
          padding: '20px 18px',
          background: '#ffffff'
        }}
      >
        {/* Day of Week Headers */}
        <div
          style={{
            display: 'grid',
            gridTemplateColumns: 'repeat(7, 1fr)',
            gap: '8px',
            textAlign: 'center',
            marginBottom: '14px'
          }}
        >
          <div style={{ fontSize: '13.5px', fontWeight: '850', color: '#ef4444' }}>{isMr ? 'र' : 'Sun'}</div>
          <div style={{ fontSize: '13.5px', fontWeight: '850', color: '#64748b' }}>{isMr ? 'सो' : 'Mon'}</div>
          <div style={{ fontSize: '13.5px', fontWeight: '850', color: '#64748b' }}>{isMr ? 'मं' : 'Tue'}</div>
          <div style={{ fontSize: '13.5px', fontWeight: '850', color: '#64748b' }}>{isMr ? 'बु' : 'Wed'}</div>
          <div style={{ fontSize: '13.5px', fontWeight: '850', color: '#64748b' }}>{isMr ? 'गु' : 'Thu'}</div>
          <div style={{ fontSize: '13.5px', fontWeight: '850', color: '#64748b' }}>{isMr ? 'शु' : 'Fri'}</div>
          <div style={{ fontSize: '13.5px', fontWeight: '850', color: '#64748b' }}>{isMr ? 'श' : 'Sat'}</div>
        </div>

        {/* 7 Columns Date Grid */}
        <div
          style={{
            display: 'grid',
            gridTemplateColumns: 'repeat(7, 1fr)',
            gap: '8px',
          }}
        >
          {/* Empty cells before the 1st */}
          {Array.from({ length: firstDayOffset }).map((_, idx) => (
            <div key={`empty-${idx}`} style={{ minHeight: '50px' }} />
          ))}

          {/* Days of the Month */}
          {Array.from({ length: daysInMonth }).map((_, idx) => {
            const dayNum = idx + 1;
            const status = getDayStatus(dayNum);
            const isSelected = selectedDay === dayNum;

            // Render specific styling based on status
            if (status.type === 'today') {
              return (
                <div
                  key={dayNum}
                  onClick={() => setSelectedDay(dayNum)}
                  style={{
                    background: '#2b39b0',
                    border: '2px solid #6366f1',
                    borderRadius: '16px',
                    color: '#ffffff',
                    minHeight: '52px',
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    justifyContent: 'center',
                    cursor: 'pointer',
                    boxShadow: '0 4px 14px rgba(43, 57, 176, 0.35)',
                    transition: 'all 0.15s ease'
                  }}
                  title={isMr ? 'आजची हजेरी (Today)' : 'Today'}
                >
                  <span style={{ fontSize: '16px', fontWeight: '900', lineHeight: 1 }}>{dayNum}</span>
                  <span style={{ fontSize: '10px', fontWeight: '800', marginTop: '2px', opacity: 0.95 }}>
                    {isMr ? 'आज' : 'Today'}
                  </span>
                </div>
              );
            }

            if (status.type === 'present') {
              return (
                <div
                  key={dayNum}
                  onClick={() => setSelectedDay(dayNum)}
                  style={{
                    background: '#ecfdf5',
                    border: isSelected ? '2px solid #059669' : '1.5px solid #a7f3d0',
                    borderRadius: '16px',
                    color: '#065f46',
                    minHeight: '52px',
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    justifyContent: 'center',
                    cursor: 'pointer',
                    transition: 'all 0.15s ease',
                    boxShadow: isSelected ? '0 0 0 2px rgba(5, 150, 105, 0.2)' : 'none'
                  }}
                  title={isMr ? `तारीख ${dayNum} - हजर (Present)` : `Day ${dayNum} - Present`}
                >
                  <span style={{ fontSize: '16px', fontWeight: '850', lineHeight: 1 }}>{dayNum}</span>
                  <span style={{ fontSize: '14px', lineHeight: 0.6, color: '#059669', marginTop: '4px' }}>●</span>
                </div>
              );
            }

            if (status.type === 'holiday') {
              return (
                <div
                  key={dayNum}
                  onClick={() => setSelectedDay(dayNum)}
                  style={{
                    background: '#f8fafc',
                    border: isSelected ? '2px solid #64748b' : '1.5px solid #e2e8f0',
                    borderRadius: '16px',
                    color: '#64748b',
                    minHeight: '52px',
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    justifyContent: 'center',
                    cursor: 'pointer',
                    transition: 'all 0.15s ease'
                  }}
                  title={status.label}
                >
                  <span style={{ fontSize: '16px', fontWeight: '850', lineHeight: 1 }}>{dayNum}</span>
                  <span style={{ fontSize: '12px', lineHeight: 0.8, color: '#64748b', marginTop: '3px' }}>★</span>
                </div>
              );
            }

            if (status.type === 'leave') {
              return (
                <div
                  key={dayNum}
                  onClick={() => setSelectedDay(dayNum)}
                  style={{
                    background: '#fef2f2',
                    border: isSelected ? '2px solid #dc2626' : '1.5px solid #fecaca',
                    borderRadius: '16px',
                    color: '#dc2626',
                    minHeight: '52px',
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    justifyContent: 'center',
                    cursor: 'pointer',
                    transition: 'all 0.15s ease'
                  }}
                  title={isMr ? `तारीख ${dayNum} - रजा (Leave)` : `Day ${dayNum} - Leave`}
                >
                  <span style={{ fontSize: '16px', fontWeight: '850', lineHeight: 1 }}>{dayNum}</span>
                  <span style={{ fontSize: '14px', lineHeight: 0.6, color: '#dc2626', marginTop: '4px' }}>●</span>
                </div>
              );
            }

            if (status.type === 'incomplete') {
              return (
                <div
                  key={dayNum}
                  onClick={() => setSelectedDay(dayNum)}
                  style={{
                    background: '#fffdf5',
                    border: isSelected ? '2px solid #d97706' : '1.5px solid #fde68a',
                    borderRadius: '16px',
                    color: '#b45309',
                    minHeight: '52px',
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    justifyContent: 'center',
                    cursor: 'pointer',
                    transition: 'all 0.15s ease'
                  }}
                  title={isMr ? `तारीख ${dayNum} - अपूर्ण (Half Day)` : `Day ${dayNum} - Incomplete`}
                >
                  <span style={{ fontSize: '16px', fontWeight: '850', lineHeight: 1 }}>{dayNum}</span>
                  <span style={{ fontSize: '14px', lineHeight: 0.6, color: '#d97706', marginTop: '4px' }}>●</span>
                </div>
              );
            }

            if (status.type === 'sunday') {
              return (
                <div
                  key={dayNum}
                  onClick={() => setSelectedDay(dayNum)}
                  style={{
                    background: '#fef2f2',
                    border: isSelected ? '2px solid #ef4444' : '1.5px solid #fee2e2',
                    borderRadius: '16px',
                    color: '#ef4444',
                    minHeight: '52px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    cursor: 'pointer',
                    transition: 'all 0.15s ease'
                  }}
                  title={isMr ? `रविवार सुट्टी (Sunday)` : `Sunday Off`}
                >
                  <span style={{ fontSize: '16px', fontWeight: '850', lineHeight: 1 }}>{dayNum}</span>
                </div>
              );
            }

            // Future days
            return (
              <div
                key={dayNum}
                style={{
                  minHeight: '52px',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  color: '#cbd5e1',
                  fontSize: '16px',
                  fontWeight: '750',
                  userSelect: 'none'
                }}
              >
                {dayNum}
              </div>
            );
          })}
        </div>
      </div>

      {/* 4. Selected Day Details Strip */}
      <div
        style={{
          background: '#f8fafc',
          border: '1.5px solid #e2e8f0',
          borderRadius: '18px',
          padding: '14px 18px',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between',
          flexWrap: 'wrap',
          gap: '12px'
        }}
      >
        <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
          <div
            style={{
              width: '40px',
              height: '40px',
              borderRadius: '12px',
              background: selectedDayInfo.type === 'today' ? '#2b39b0' : selectedDayInfo.type === 'present' ? '#059669' : selectedDayInfo.type === 'incomplete' ? '#d97706' : selectedDayInfo.type === 'leave' || selectedDayInfo.type === 'sunday' ? '#dc2626' : '#64748b',
              color: '#ffffff',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              fontWeight: '900',
              fontSize: '16px'
            }}
          >
            {selectedDay}
          </div>

          <div>
            <div style={{ fontSize: '14px', fontWeight: '850', color: '#0f172a' }}>
              {selectedDay} {isMr ? monthNamesMr[calMonth] : monthNamesEn[calMonth]} {calYear} • {selectedDayInfo.label}
            </div>
            <div style={{ fontSize: '12px', color: '#64748b', marginTop: '2px', fontWeight: '600' }}>
              {selectedDayInfo.inTime ? (
                <span>
                  🟢 In: <strong>{selectedDayInfo.inTime}</strong> • 🟠 Out: <strong>{selectedDayInfo.outTime}</strong>
                </span>
              ) : (
                <span>{selectedDayInfo.note || (isMr ? 'हजेरी नोंद तपशील' : 'Duty Log')}</span>
              )}
            </div>
          </div>
        </div>

        <button
          type="button"
          onClick={() => setShowFullTable(!showFullTable)}
          style={{
            background: '#ffffff',
            border: '1px solid #cbd5e1',
            borderRadius: '10px',
            padding: '6px 14px',
            fontSize: '12px',
            fontWeight: '750',
            color: '#334155',
            cursor: 'pointer',
            display: 'flex',
            alignItems: 'center',
            gap: '6px'
          }}
        >
          <span>📋</span>
          <span>{showFullTable ? (isMr ? 'नोंदवही लपवा' : 'Hide Table') : (isMr ? 'संपूर्ण नोंदवही पहा' : 'View Full Table')}</span>
        </button>
      </div>

      {/* 5. Expandable Full Table Log */}
      {showFullTable && (
        <div className="karyakarta-table-wrapper" style={{ marginTop: '4px' }}>
          <table className="karyakarta-table">
            <thead>
              <tr>
                <th>{isMr ? 'तारीख' : 'Date'}</th>
                <th>{isMr ? 'पंच इन' : 'Punch In'}</th>
                <th>{isMr ? 'पंच आउट' : 'Punch Out'}</th>
                <th>{isMr ? 'सेल्फी फोटो' : 'Photos'}</th>
                <th>{isMr ? 'स्थान' : 'Location'}</th>
                <th>{isMr ? 'स्थिती' : 'Status'}</th>
              </tr>
            </thead>
            <tbody>
              {attendanceHistory && attendanceHistory.length > 0 ? (
                attendanceHistory.map((record, i) => (
                  <tr key={record.id || i}>
                    <td style={{ fontWeight: '750', color: '#0f172a' }}>{record.date}</td>
                    <td>
                      <span style={{ color: '#059669', fontWeight: '700', display: 'inline-flex', alignItems: 'center', gap: '4px' }}>
                        <LogIn size={13} />
                        {record.punch_in_time || record.punchIn || '-'}
                      </span>
                    </td>
                    <td>
                      <span style={{ color: '#ea580c', fontWeight: '700', display: 'inline-flex', alignItems: 'center', gap: '4px' }}>
                        <LogOut size={13} />
                        {record.punch_out_time || record.punchOut || '-'}
                      </span>
                    </td>
                    <td>
                      <div style={{ display: 'flex', gap: '6px' }}>
                        {(record.punch_in_photo || record.punchInPhoto) && (
                          <img
                            src={record.punch_in_photo || record.punchInPhoto}
                            alt="Punch In"
                            style={{ width: '32px', height: '32px', borderRadius: '6px', objectFit: 'cover', border: '1px solid #86efac' }}
                          />
                        )}
                        {(record.punch_out_photo || record.punchOutPhoto) && (
                          <img
                            src={record.punch_out_photo || record.punchOutPhoto}
                            alt="Punch Out"
                            style={{ width: '32px', height: '32px', borderRadius: '6px', objectFit: 'cover', border: '1px solid #fdba74' }}
                          />
                        )}
                      </div>
                    </td>
                    <td style={{ maxWidth: '240px', fontSize: '12px', color: '#475569' }}>
                      📍 {record.location_note || record.address || 'सांगली परिसर'}
                    </td>
                    <td>
                      <span className={`badge-status ${record.status === 'Present' ? 'status-completed' : 'status-progress'}`}>
                        {record.status === 'Present' ? (isMr ? 'हजर' : 'Present') : record.status}
                      </span>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan="6" style={{ textAlign: 'center', padding: '1.5rem', color: '#64748b' }}>
                    {isMr ? 'कोणतीही नोंद उपलब्ध नाही' : 'No records found'}
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
