import React, { useState } from 'react';
import {
  Calendar,
  Search,
  CalendarCheck,
  PhoneCall,
  MessageCircle,
  MapPin,
  Plus,
  Video,
  Users,
  Clock,
  Copy,
  Check,
  MessageSquare,
  ChevronDown,
  LayoutGrid,
  List,
  Phone,
  UserCheck,
  ExternalLink,
  Bell
} from 'lucide-react';

export default function ContactsTab({
  isMr,
  t,
  votersList = [],
  meetingsList = [],
  contactTab = 'contacts',
  setContactTab,
  contactSentimentFilter,
  setContactSentimentFilter,
  contactSearchQuery = '',
  setContactSearchQuery,
  activeVisitContact,
  setActiveVisitContact,
  visitFormData,
  setVisitFormData,
  handleSaveVisitLog,
  currentCoords,
  isSchedulingMeeting,
  setIsSchedulingMeeting,
  newMeeting,
  setNewMeeting,
  handleScheduleMeeting,
}) {
  const [selectedContactForOptions, setSelectedContactForOptions] = useState(null);
  const [copiedPhone, setCopiedPhone] = useState(false);
  const [copiedMeetLink, setCopiedMeetLink] = useState(false);
  const [viewMode, setViewMode] = useState('cards'); // 'cards' (default modern) or 'table'
  const [contactStatusFilter, setContactStatusFilter] = useState('All'); // 'All' | 'Contacted' | 'Pending'

  return (
    <div className="module-container" style={{ display: 'flex', flexDirection: 'column', gap: '1.5rem', width: '100%', maxWidth: '1400px', margin: '0 auto', paddingBottom: '2rem' }}>
      {/* Hero Header & Sub-Tab Switcher matching mockup */}
      <div
        style={{
          background: 'linear-gradient(135deg, #091a48 0%, #133374 55%, #1e4599 100%)',
          borderRadius: '20px',
          padding: '18px 20px',
          color: '#ffffff',
          boxShadow: '0 8px 24px rgba(9, 26, 72, 0.22)',
          display: 'flex',
          flexDirection: 'column',
          gap: '14px',
        }}
      >
        <div>
          <h1
            style={{
              margin: 0,
              fontSize: '22px',
              fontWeight: 900,
              color: '#ffffff',
              letterSpacing: '-0.3px',
              display: 'flex',
              alignItems: 'center',
              gap: '10px',
            }}
          >
            <span>🗓️</span>
            <span>{isMr ? 'मतदार संपर्क व बैठका' : 'Voter Contacts & Meetings'}</span>
          </h1>
          <div style={{ fontSize: '13px', color: '#93c5fd', marginTop: '4px', fontWeight: 550 }}>
            {isMr ? 'वॉर्ड नियोजन व आगामी प्रचार सभा' : 'Ward Planning & Campaign Outreach'}
          </div>
        </div>

        {/* Capsule Switcher */}
        <div
          style={{
            display: 'flex',
            background: 'rgba(255, 255, 255, 0.96)',
            borderRadius: '16px',
            padding: '4px',
            gap: '6px',
            boxShadow: '0 4px 12px rgba(0, 0, 0, 0.1)',
          }}
        >
          <button
            type="button"
            onClick={() => setContactTab('contacts')}
            style={{
              flex: 1,
              padding: '9px 16px',
              borderRadius: '12px',
              border: 'none',
              background: contactTab === 'contacts' ? 'linear-gradient(135deg, #2563eb, #1d4ed8)' : 'transparent',
              color: contactTab === 'contacts' ? '#ffffff' : '#1e293b',
              fontWeight: 800,
              fontSize: '13.5px',
              cursor: 'pointer',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              gap: '8px',
              boxShadow: contactTab === 'contacts' ? '0 4px 14px rgba(37,99,235,0.35)' : 'none',
              transition: 'all 0.18s ease',
            }}
          >
            <span>{isMr ? 'मतदार यादी' : 'Voter Roll'}</span>
            <span
              style={{
                fontSize: '11.5px',
                padding: '2px 8px',
                borderRadius: '8px',
                background: contactTab === 'contacts' ? 'rgba(255,255,255,0.25)' : '#e2e8f0',
                color: contactTab === 'contacts' ? '#ffffff' : '#475569',
                fontWeight: 800,
              }}
            >
              {votersList.length > 0 ? votersList.length.toLocaleString('en-IN') : '1,240'}
            </span>
          </button>

          <button
            type="button"
            onClick={() => setContactTab('meetings')}
            style={{
              flex: 1,
              padding: '9px 16px',
              borderRadius: '12px',
              border: 'none',
              background: contactTab === 'meetings' ? 'linear-gradient(135deg, #2563eb, #1d4ed8)' : 'transparent',
              color: contactTab === 'meetings' ? '#ffffff' : '#1e293b',
              fontWeight: 800,
              fontSize: '13.5px',
              cursor: 'pointer',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              gap: '8px',
              boxShadow: contactTab === 'meetings' ? '0 4px 14px rgba(37,99,235,0.35)' : 'none',
              transition: 'all 0.18s ease',
            }}
          >
            <span>🗓️ {isMr ? 'बैठका व सभा' : 'Meetings & Sessions'}</span>
            <span
              style={{
                fontSize: '11.5px',
                padding: '2px 8px',
                borderRadius: '8px',
                background: contactTab === 'meetings' ? 'rgba(255,255,255,0.25)' : '#e2e8f0',
                color: contactTab === 'meetings' ? '#ffffff' : '#475569',
                fontWeight: 800,
              }}
            >
              {isMr ? `(${meetingsList.length > 0 ? meetingsList.length : '३'})` : `(${meetingsList.length > 0 ? meetingsList.length : '3'})`}
            </span>
          </button>
        </div>
      </div>

      {contactTab === 'contacts' && (() => {
        const filteredContacts = votersList.filter((contact) => {
          if (contactStatusFilter === 'Contacted' && !contact.contacted) return false;
          if (contactStatusFilter === 'Pending' && contact.contacted) return false;
          if (!contactSearchQuery.trim()) return true;
          const q = contactSearchQuery.toLowerCase();
          return (
            (contact.name && contact.name.toLowerCase().includes(q)) ||
            (contact.phone && contact.phone.includes(q)) ||
            (contact.ward && contact.ward.toLowerCase().includes(q))
          );
        });

        return (
          <>

            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '12px', background: '#ffffff', padding: '12px 16px', borderRadius: '14px', border: '1.5px solid #e2e8f0', boxShadow: '0 2px 6px rgba(0,0,0,0.02)' }}>
              <div style={{ display: 'flex', gap: '8px', flexWrap: 'wrap', alignItems: 'center' }}>
                {[
                  // { key: 'All', label: isMr ? 'सर्व मतदार' : 'All Voters' },
                  // { key: 'Contacted', label: isMr ? '✓ संपर्क झालेले' : '✓ Contacted' },
                  // { key: 'Pending', label: isMr ? '⏳ संपर्क बाकी' : '⏳ Pending' }
                ].map((item) => (
                  <button
                    key={item.key}
                    type="button"
                    onClick={() => setContactStatusFilter(item.key)}
                    style={{
                      padding: '6px 14px',
                      borderRadius: '8px',
                      fontSize: '12px',
                      fontWeight: 700,
                      cursor: 'pointer',
                      border: 'none',
                      background: contactStatusFilter === item.key ? '#0284c7' : '#f1f5f9',
                      color: contactStatusFilter === item.key ? '#ffffff' : '#475569',
                      transition: 'all 0.15s ease'
                    }}
                  >
                    {item.label}
                  </button>
                ))}
              </div>

              <div style={{ display: 'flex', alignItems: 'center', gap: '10px', flexWrap: 'wrap' }}>
                {/* Search Input */}
                <div className="worker-filter-input-wrap" style={{ position: 'relative', minWidth: '220px' }}>
                  <Search size={14} color="#94a3b8" style={{ position: 'absolute', left: '10px', top: '50%', transform: 'translateY(-50%)' }} />
                  <input
                    type="text"
                    placeholder={isMr ? "नाव, फोन किंवा प्रभाग शोधा..." : "Search voters by name, phone..."}
                    value={contactSearchQuery}
                    onChange={(e) => setContactSearchQuery(e.target.value)}
                    style={{
                      width: '100%',
                      padding: '7px 24px 7px 30px',
                      borderRadius: '8px',
                      border: '1px solid #cbd5e1',
                      fontSize: '12px',
                      outline: 'none',
                      boxSizing: 'border-box'
                    }}
                  />
                  {contactSearchQuery && (
                    <button
                      type="button"
                      onClick={() => setContactSearchQuery('')}
                      style={{ position: 'absolute', right: '10px', top: '50%', transform: 'translateY(-50%)', border: 'none', background: 'none', cursor: 'pointer', color: '#94a3b8', fontSize: '12px' }}
                    >
                      ✕
                    </button>
                  )}
                </div>

                {/* View Switcher: Cards vs Table */}
                <div style={{ display: 'flex', alignItems: 'center', gap: '3px', background: '#f1f5f9', padding: '3px', borderRadius: '8px', border: '1px solid #e2e8f0' }}>
                  <button
                    type="button"
                    onClick={() => setViewMode('cards')}
                    style={{
                      padding: '5px 10px',
                      borderRadius: '6px',
                      border: 'none',
                      background: viewMode === 'cards' ? '#ffffff' : 'transparent',
                      color: viewMode === 'cards' ? '#0284c7' : '#64748b',
                      fontWeight: 750,
                      fontSize: '11.5px',
                      cursor: 'pointer',
                      boxShadow: viewMode === 'cards' ? '0 1px 3px rgba(0,0,0,0.08)' : 'none',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '4px'
                    }}
                    title={isMr ? 'कार्ड व्ह्यू' : 'Cards View'}
                  >
                    <LayoutGrid size={13} />
                    <span>{isMr ? 'कार्ड' : 'Cards'}</span>
                  </button>
                  <button
                    type="button"
                    onClick={() => setViewMode('table')}
                    style={{
                      padding: '5px 10px',
                      borderRadius: '6px',
                      border: 'none',
                      background: viewMode === 'table' ? '#ffffff' : 'transparent',
                      color: viewMode === 'table' ? '#0284c7' : '#64748b',
                      fontWeight: 750,
                      fontSize: '11.5px',
                      cursor: 'pointer',
                      boxShadow: viewMode === 'table' ? '0 1px 3px rgba(0,0,0,0.08)' : 'none',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '4px'
                    }}
                    title={isMr ? 'टेबल व्ह्यू' : 'Table View'}
                  >
                    <List size={13} />
                    <span>{isMr ? 'टेबल' : 'Table'}</span>
                  </button>
                </div>
              </div>
            </div>

            {/* 1. CARDS VIEW (CLEAN, SPACIOUS, NO OVERLAPPING COLUMNS) */}
            {viewMode === 'cards' && (
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(320px, 1fr))', gap: '14px' }}>
                {filteredContacts.length > 0 ? (
                  filteredContacts.map((contact, i) => (
                    <div
                      key={contact.id || i}
                      style={{
                        background: '#ffffff',
                        border: '1.5px solid #e2e8f0',
                        borderRadius: '16px',
                        padding: '16px 18px',
                        display: 'flex',
                        flexDirection: 'column',
                        gap: '12px',
                        boxShadow: '0 2px 8px rgba(15, 23, 42, 0.03)',
                        transition: 'all 0.15s ease'
                      }}
                    >
                      {/* Top Row: Avatar + Name + Contact Status Tag */}
                      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', gap: '10px' }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                          <div
                            style={{
                              width: '42px',
                              height: '42px',
                              borderRadius: '50%',
                              background: 'linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%)',
                              color: '#1d4ed8',
                              display: 'flex',
                              alignItems: 'center',
                              justifyContent: 'center',
                              fontWeight: 800,
                              fontSize: '1rem',
                              border: '1.5px solid #bfdbfe',
                              flexShrink: 0
                            }}
                          >
                            {contact.name.charAt(0)}
                          </div>
                          <div>
                            <div style={{ fontWeight: 850, fontSize: '15px', color: '#0f172a', lineHeight: 1.2 }}>
                              {contact.name}
                            </div>
                            <div style={{ display: 'flex', alignItems: 'center', gap: '6px', marginTop: '4px', flexWrap: 'wrap' }}>
                              <span style={{ background: '#f8fafc', padding: '2px 8px', borderRadius: '6px', fontSize: '11px', color: '#475569', fontWeight: 650, border: '1px solid #e2e8f0' }}>
                                📍 {contact.ward}
                              </span>
                            </div>
                          </div>
                        </div>

                        {/* Contact Status Badge */}
                        <span
                          style={{
                            padding: '4px 10px',
                            borderRadius: '20px',
                            fontSize: '11px',
                            fontWeight: 750,
                            background: contact.contacted ? '#ecfdf5' : '#fff7ed',
                            color: contact.contacted ? '#15803d' : '#c2410c',
                            border: contact.contacted ? '1px solid #bbf7d0' : '1px solid #fed7aa',
                            whiteSpace: 'nowrap',
                            flexShrink: 0
                          }}
                        >
                          {contact.contacted ? (isMr ? '✓ संपर्क पूर्ण' : '✓ Contacted') : (isMr ? '⏳ संपर्क बाकी' : '⏳ Pending')}
                        </span>
                      </div>

                      {/* Middle Details: Mobile Phone with Clickable Call */}
                      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', background: '#f8fafc', padding: '8px 12px', borderRadius: '10px', border: '1px solid #f1f5f9' }}>
                        <a
                          href={`tel:${contact.phone}`}
                          style={{ display: 'flex', alignItems: 'center', gap: '8px', color: '#0284c7', fontWeight: 750, fontSize: '13.5px', textDecoration: 'none' }}
                          title={isMr ? "कॉल करा" : "Call voter"}
                        >
                          <PhoneCall size={15} color="#0284c7" />
                          <span>{contact.phone}</span>
                        </a>

                        <span style={{ fontSize: '11px', color: '#64748b', fontWeight: 600 }}>
                          {isMr ? 'थेट संपर्क' : 'Direct Line'}
                        </span>
                      </div>

                      {/* Bottom Action: Big Contact Options Trigger */}
                      <button
                        type="button"
                        onClick={() => setSelectedContactForOptions(contact)}
                        style={{
                          width: '100%',
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                          gap: '8px',
                          padding: '10px 14px',
                          background: 'linear-gradient(135deg, #0284c7 0%, #0369a1 100%)',
                          color: '#ffffff',
                          border: 'none',
                          borderRadius: '10px',
                          fontSize: '13px',
                          fontWeight: 800,
                          cursor: 'pointer',
                          boxShadow: '0 2px 8px rgba(2, 132, 199, 0.25)',
                          transition: 'all 0.15s ease'
                        }}
                      >
                        <PhoneCall size={15} />
                        <span>{isMr ? 'संपर्क पर्याय' : 'Contact Options'}</span>
                        <ChevronDown size={14} />
                      </button>
                    </div>
                  ))
                ) : (
                  <div style={{ gridColumn: '1 / -1', textAlign: 'center', padding: '3rem', background: '#ffffff', borderRadius: '14px', border: '1px solid #e2e8f0', color: '#64748b' }}>
                    {isMr ? 'कोणताही मतदार संपर्क आढळला नाही.' : 'No voter contacts found matching criteria.'}
                  </div>
                )}
              </div>
            )}

            {/* 2. TABLE VIEW (CLEAN, PROPER MIN-WIDTH, NO OVERLAPPING COLUMNS) */}
            {viewMode === 'table' && (
              <div className="card" style={{ padding: 0, overflow: 'hidden', border: '1.5px solid #e2e8f0', borderRadius: '14px' }}>
                <div style={{ overflowX: 'auto', width: '100%' }}>
                  <table style={{ width: '100%', minWidth: '700px', borderCollapse: 'collapse', textAlign: 'left' }}>
                    <thead>
                      <tr style={{ background: '#f8fafc', borderBottom: '1.5px solid #e2e8f0' }}>
                        <th style={{ padding: '12px 16px', fontSize: '12px', fontWeight: 800, color: '#475569', textTransform: 'uppercase' }}>
                          {isMr ? 'मतदाराचे नाव' : 'Voter / Citizen Name'}
                        </th>
                        <th style={{ padding: '12px 16px', fontSize: '12px', fontWeight: 800, color: '#475569', textTransform: 'uppercase' }}>
                          {isMr ? 'मोबाईल नंबर' : 'Mobile Number'}
                        </th>
                        <th style={{ padding: '12px 16px', fontSize: '12px', fontWeight: 800, color: '#475569', textTransform: 'uppercase' }}>
                          {isMr ? 'बूथ / प्रभाग' : 'Booth / Ward'}
                        </th>
                        <th style={{ padding: '12px 16px', fontSize: '12px', fontWeight: 800, color: '#475569', textTransform: 'uppercase' }}>
                          {isMr ? 'संपर्क स्थिती' : 'Contact Status'}
                        </th>
                        <th style={{ padding: '12px 16px', fontSize: '12px', fontWeight: 800, color: '#475569', textTransform: 'uppercase', textAlign: 'right' }}>
                          {isMr ? 'संपर्क पर्याय' : 'Contact Action'}
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      {filteredContacts.length > 0 ? (
                        filteredContacts.map((contact, i) => (
                          <tr key={contact.id || i} style={{ borderBottom: '1px solid #f1f5f9' }}>
                            <td style={{ padding: '12px 16px', whiteSpace: 'nowrap' }}>
                              <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                                <div style={{ width: '36px', height: '36px', borderRadius: '50%', backgroundColor: '#eff6ff', color: '#1d4ed8', display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: 800, fontSize: '0.85rem', border: '1px solid #bfdbfe' }}>
                                  {contact.name.charAt(0)}
                                </div>
                                <div style={{ fontWeight: 800, color: '#0f172a', fontSize: '13.5px' }}>{contact.name}</div>
                              </div>
                            </td>
                            <td style={{ padding: '12px 16px', color: '#0284c7', fontWeight: 700, fontSize: '13px', whiteSpace: 'nowrap' }}>
                              <a href={`tel:${contact.phone}`} style={{ color: '#0284c7', textDecoration: 'none', display: 'inline-flex', alignItems: 'center', gap: '6px' }}>
                                <PhoneCall size={14} />
                                <span>{contact.phone}</span>
                              </a>
                            </td>
                            <td style={{ padding: '12px 16px', whiteSpace: 'nowrap' }}>
                              <span style={{ background: '#f8fafc', padding: '4px 10px', borderRadius: '6px', fontSize: '11.5px', border: '1px solid #e2e8f0', color: '#475569', fontWeight: 650 }}>
                                📍 {contact.ward}
                              </span>
                            </td>
                            <td style={{ padding: '12px 16px', whiteSpace: 'nowrap' }}>
                              <span
                                style={{
                                  padding: '4px 10px',
                                  borderRadius: '20px',
                                  fontSize: '11.5px',
                                  fontWeight: 750,
                                  background: contact.contacted ? '#ecfdf5' : '#fff7ed',
                                  color: contact.contacted ? '#15803d' : '#c2410c',
                                  border: contact.contacted ? '1px solid #bbf7d0' : '1px solid #fed7aa',
                                  whiteSpace: 'nowrap',
                                  display: 'inline-block'
                                }}
                              >
                                {contact.contacted ? (isMr ? '✓ संपर्क पूर्ण' : '✓ Contacted') : (isMr ? '⏳ संपर्क बाकी' : '⏳ Pending')}
                              </span>
                            </td>
                            <td style={{ padding: '12px 16px', textAlign: 'right', whiteSpace: 'nowrap' }}>
                              <button
                                type="button"
                                onClick={() => setSelectedContactForOptions(contact)}
                                style={{
                                  display: 'inline-flex',
                                  alignItems: 'center',
                                  gap: '6px',
                                  padding: '7px 14px',
                                  background: '#0284c7',
                                  color: '#ffffff',
                                  border: 'none',
                                  borderRadius: '8px',
                                  fontSize: '12px',
                                  fontWeight: 750,
                                  cursor: 'pointer',
                                  boxShadow: '0 2px 4px rgba(2, 132, 199, 0.2)'
                                }}
                              >
                                <PhoneCall size={13} />
                                <span>{isMr ? 'संपर्क पर्याय' : 'Contact Options'}</span>
                                <ChevronDown size={12} />
                              </button>
                            </td>
                          </tr>
                        ))
                      ) : (
                        <tr>
                          <td colSpan="5" style={{ textAlign: 'center', padding: '3rem', color: '#64748b' }}>
                            {isMr ? 'कोणताही मतदार संपर्क आढळला नाही.' : 'No voter contacts found matching criteria.'}
                          </td>
                        </tr>
                      )}
                    </tbody>
                  </table>
                </div>
              </div>
            )}

            {/* 3. DEDICATED "CONTACT OPTIONS" MODAL (POPUP WITH ALL OPTIONS) */}
            {selectedContactForOptions && (
              <div className="responsive-modal-overlay" style={{ zIndex: 1100 }}>
                <div className="responsive-modal-content" style={{ maxWidth: '440px', padding: '20px', borderRadius: '20px' }}>
                  {/* Modal Header */}
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '16px', borderBottom: '1px solid #e2e8f0', paddingBottom: '14px' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                      <div style={{ width: '46px', height: '46px', borderRadius: '50%', background: '#eff6ff', color: '#1d4ed8', display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: 800, fontSize: '1.1rem', border: '1.5px solid #bfdbfe' }}>
                        {selectedContactForOptions.name.charAt(0)}
                      </div>
                      <div>
                        <h3 style={{ margin: 0, fontSize: '17px', fontWeight: 850, color: '#0f172a' }}>
                          {selectedContactForOptions.name}
                        </h3>
                        <div style={{ fontSize: '12px', color: '#64748b', marginTop: '2px', display: 'flex', alignItems: 'center', gap: '6px' }}>
                          <span>📍 {selectedContactForOptions.ward}</span>
                          <span>•</span>
                          <span style={{ fontWeight: 700, color: '#0284c7' }}>📞 {selectedContactForOptions.phone}</span>
                        </div>
                      </div>
                    </div>
                    <button
                      type="button"
                      onClick={() => setSelectedContactForOptions(null)}
                      style={{ border: 'none', background: '#f1f5f9', width: '32px', height: '32px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer', fontSize: '16px', color: '#64748b' }}
                    >
                      ✕
                    </button>
                  </div>

                  <div style={{ fontSize: '12px', fontWeight: 750, color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.4px', marginBottom: '12px' }}>
                    {isMr ? 'उपलब्ध संपर्क पर्याय (Choose Contact Option):' : 'Available Contact Actions:'}
                  </div>

                  {/* All Contact Options */}
                  <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
                    {/* Option 1: Direct Phone Call */}
                    <a
                      href={`tel:${selectedContactForOptions.phone}`}
                      style={{
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        padding: '13px 16px',
                        borderRadius: '12px',
                        background: '#ecfdf5',
                        border: '1.5px solid #a7f3d0',
                        color: '#065f46',
                        textDecoration: 'none',
                        fontWeight: 750,
                        fontSize: '13.5px',
                        transition: 'all 0.15s ease'
                      }}
                    >
                      <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                        <div style={{ width: '36px', height: '36px', borderRadius: '10px', background: '#dcfce7', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#059669' }}>
                          <PhoneCall size={18} />
                        </div>
                        <div>
                          <div>{isMr ? 'फोन कॉल' : ' Calling'}</div>
                          <div style={{ fontSize: '11.5px', color: '#059669', fontWeight: 600 }}>{selectedContactForOptions.phone}</div>
                        </div>
                      </div>
                      <span style={{ fontSize: '12px', background: '#059669', color: '#fff', padding: '4px 10px', borderRadius: '8px', fontWeight: 750 }}>
                        {isMr ? 'कॉल' : 'Call'}
                      </span>
                    </a>

                    {/* Option 2: WhatsApp Chat */}
                    <a
                      href={`https://wa.me/91${(selectedContactForOptions.phone || '').replace(/[^0-9]/g, '')}`}
                      target="_blank"
                      rel="noreferrer"
                      style={{
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        padding: '13px 16px',
                        borderRadius: '12px',
                        background: '#f0fdf4',
                        border: '1.5px solid #86efac',
                        color: '#14532d',
                        textDecoration: 'none',
                        fontWeight: 750,
                        fontSize: '13.5px',
                        transition: 'all 0.15s ease'
                      }}
                    >
                      <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                        <div style={{ width: '36px', height: '36px', borderRadius: '10px', background: '#dcfce7', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#16a34a' }}>
                          <MessageCircle size={18} />
                        </div>
                        <div>
                          <div>{isMr ? 'व्हॉट्सॲप संवाद' : 'WhatsApp Message'}</div>
                          <div style={{ fontSize: '11.5px', color: '#16a34a', fontWeight: 600 }}>{isMr ? 'थेट संदेश पाठवा' : 'Send WhatsApp Message'}</div>
                        </div>
                      </div>
                      <span style={{ fontSize: '12px', background: '#16a34a', color: '#fff', padding: '4px 10px', borderRadius: '8px', fontWeight: 750 }}>
                        WhatsApp
                      </span>
                    </a>


                    {/* Option 6: Copy Phone Number */}
                    <button
                      type="button"
                      onClick={() => {
                        if (selectedContactForOptions.phone) {
                          navigator.clipboard.writeText(selectedContactForOptions.phone);
                          setCopiedPhone(true);
                          setTimeout(() => setCopiedPhone(false), 2000);
                        }
                      }}
                      style={{
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        gap: '8px',
                        padding: '10px',
                        borderRadius: '10px',
                        background: copiedPhone ? '#dcfce7' : '#f1f5f9',
                        border: copiedPhone ? '1.5px solid #86efac' : '1px solid #cbd5e1',
                        color: copiedPhone ? '#15803d' : '#475569',
                        fontSize: '12.5px',
                        fontWeight: 750,
                        cursor: 'pointer',
                        marginTop: '4px'
                      }}
                    >
                      {copiedPhone ? <Check size={16} /> : <Copy size={16} />}
                      <span>{copiedPhone ? (isMr ? '✓ नंबर कॉपी झाला!' : '✓ Number Copied!') : (isMr ? 'मोबाईल नंबर कॉपी करा' : 'Copy Mobile Number')}</span>
                    </button>
                  </div>
                </div>
              </div>
            )}

            {/* Visit Log Recording Modal */}
            {activeVisitContact && (
              <div className="responsive-modal-overlay">
                <div className="responsive-modal-content" style={{ maxWidth: '500px' }}>
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px', borderBottom: '1px solid #e2e8f0', paddingBottom: '12px' }}>
                    <h3 style={{ margin: 0, fontSize: '17px', fontWeight: 800, color: '#0f172a', display: 'flex', alignItems: 'center', gap: '8px' }}>
                      <span>🤝</span>
                      <span>{isMr ? 'मतदार प्रत्यक्ष भेट नोंद (Visit Log)' : 'Record Doorstep Voter Visit Log'}</span>
                    </h3>
                    <button type="button" onClick={() => setActiveVisitContact(null)} style={{ border: 'none', background: 'none', cursor: 'pointer', fontSize: '18px', color: '#94a3b8' }}>✕</button>
                  </div>

                  <form onSubmit={handleSaveVisitLog} style={{ display: 'flex', flexDirection: 'column', gap: '14px' }}>
                    <div style={{ background: '#f8fafc', padding: '12px', borderRadius: '10px', border: '1px solid #e2e8f0' }}>
                      <div style={{ fontSize: '11px', color: '#64748b', fontWeight: '700' }}>
                        {isMr ? 'मतदाराची माहिती' : 'Voter Information'}
                      </div>
                      <div style={{ fontSize: '15px', fontWeight: '800', color: '#0f172a', marginTop: '2px' }}>
                        {activeVisitContact.name} ({activeVisitContact.phone || (isMr ? 'फोन नाही' : 'No Phone')})
                      </div>
                      <div style={{ fontSize: '11.5px', color: '#64748b', marginTop: '2px' }}>
                        {activeVisitContact.ward}
                      </div>
                    </div>

                    <div>
                      <label style={{ display: 'block', fontSize: '12.5px', fontWeight: 700, color: '#334155', marginBottom: '4px' }}>
                        {isMr ? 'राजकीय कल / मतदाराची भावना *' : 'Voter Political Sentiment / Response *'}
                      </label>
                      <select
                        className="form-input"
                        value={visitFormData.status}
                        onChange={(e) => setVisitFormData({ ...visitFormData, status: e.target.value })}
                      >
                        <option value="Supportive">🟢 {isMr ? 'अनुकूल / पाठीराखा (Supportive)' : 'Supportive (In Favor)'}</option>
                        <option value="Undecided">⚪ {isMr ? 'अनिर्णित / विचार करत आहेत (Undecided)' : 'Undecided / Neutral'}</option>
                        <option value="Needs Info">🟡 {isMr ? 'माहिती / भेट आवश्यक (Needs Info)' : 'Needs Info / Follow-Up Visit'}</option>
                        <option value="Opposed">🔴 {isMr ? 'प्रतिकूल (Opposed)' : 'Opposed (Leaning Elsewhere)'}</option>
                      </select>
                    </div>

                    <div>
                      <label style={{ display: 'block', fontSize: '12.5px', fontWeight: 700, color: '#334155', marginBottom: '4px' }}>
                        {isMr ? 'भेटीतील चर्चेचे महत्त्वाचे मुद्दे / अभिप्राय *' : 'Discussion Notes & Feedback *'}
                      </label>
                      <textarea
                        rows="3"
                        required
                        className="form-input"
                        placeholder={isMr ? "उदा. पाणी पुरवठ्याची समस्या मांडली, उमेदवाराला मत देण्याचे आश्वासन दिले..." : "Enter key voter issues, discussions, commitments..."}
                        value={visitFormData.notes}
                        onChange={(e) => setVisitFormData({ ...visitFormData, notes: e.target.value })}
                      />
                    </div>

                    <div style={{ fontSize: '11.5px', color: '#0369a1', background: '#e0f2fe', padding: '10px 12px', borderRadius: '8px', border: '1px solid #bae6fd', display: 'flex', alignItems: 'center', gap: '6px' }}>
                      <MapPin size={15} color="#0284c7" />
                      <span>📍 {currentCoords?.address || (isMr ? 'सांगली प्रभाग (GPS स्थान आपोआप जोडले जाईल)' : 'GPS Coordinates will be tagged automatically')}</span>
                    </div>

                    <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '10px', marginTop: '8px' }}>
                      <button type="button" className="btn-outline" onClick={() => setActiveVisitContact(null)}>
                        {t('cancel')}
                      </button>
                      <button type="submit" className="btn-success" style={{ fontWeight: 750, padding: '8px 20px' }}>
                        ✓ {isMr ? 'भेट जतन करा' : 'Save Visit Log'}
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            )}
          </>
        );
      })()}

      {/* Meetings Tab Content - Styled exactly like mockup, strictly without attendee count/avatars */}
      {contactTab === 'meetings' && (() => {
        const rawMeetings = (meetingsList && meetingsList.length > 0) ? meetingsList : [
          {
            id: 'm2',
            type: 'Virtual',
            categoryBadge: isMr ? '📹 व्हर्च्युअल सभा (Virtual)' : '📹 Virtual Meeting',
            badgeBg: '#2563eb',
            statusBadge: isMr ? '● उद्या' : '● Tomorrow',
            cardBorder: '#bfdbfe',
            title: isMr ? 'साप्ताहिक मोहीम आढावा ऑनलाईन' : 'Weekly Campaign Review Online',
            showVideoEmoji: true,
            time: isMr ? 'उद्या • सकाळी १०:३० वाजता' : 'Tomorrow • 10:30 AM',
            timeIcon: 'calendar',
            location: 'Google Meet',
            locationIcon: 'virtual',
            actionType: 'join',
            actionLabel: isMr ? 'Google Meet जॉईन करा' : 'Join Google Meet',
            link: 'https://meet.google.com/pqr-stuv-wxy',
            isWithin24Hours: true,
            hoursRemaining: 15,
          },
          {
            id: 'm1',
            type: 'In-person',
            categoryBadge: isMr ? '👥 प्रत्यक्ष बैठक (In-person)' : '👥 In-person Meeting',
            badgeBg: '#ea580c',
            cardBorder: '#fed7aa',
            title: isMr ? 'नवीन स्वयंसेवक प्रशिक्षण शिबिर' : 'New Volunteer Training Camp',
            time: isMr ? '१२ ऑक्टोबर २०२६ • दुपारी ०२:०० वाजता' : '12 Oct 2026 • 02:00 PM',
            timeIcon: 'clock',
            location: isMr ? 'समाज मंदिर सभागृह, गावभाग सांगली' : 'Samaj Mandir Hall, Gavbhag Sangli',
            actionType: 'map',
            actionLabel: isMr ? 'नकाशा / दिशा' : 'Map / Directions',
            isWithin24Hours: false,
            hoursRemaining: 72,
          },
          {
            id: 'm3',
            type: 'Booth',
            categoryBadge: isMr ? '🗓️ बूथ प्रमुख बैठक' : '🗓️ Booth Head Meeting',
            badgeBg: '#16a34a',
            statusBadge: isMr ? 'पुढील आठवडा' : 'Next Week',
            cardBorder: '#bbf7d0',
            title: isMr ? 'बूथ क्र. १ ते १४ मतदार यादी पडताळणी' : 'Booth No. 1 to 14 Voter Roll Verification',
            time: isMr ? 'दुपारी ०२:०० वाजता' : '02:00 PM',
            timeIcon: 'clock',
            location: isMr ? 'बूथ कृती' : 'Booth Action Center',
            combinedMeta: true,
            actionType: 'map',
            actionLabel: isMr ? 'नकाशा / दिशा' : 'Map / Directions',
            isWithin24Hours: false,
            hoursRemaining: 168,
          }
        ];

        // Sort: The closest/nearest upcoming meeting is shown first (आधी)
        const sortedMeetings = [...rawMeetings].sort((a, b) => {
          const getScore = (m) => {
            if (m.isWithin24Hours) return 1;
            if (m.hoursRemaining !== undefined) return m.hoursRemaining;
            if (m.time && (m.time.includes('उद्या') || m.time.toLowerCase().includes('tomorrow') || m.time.includes('आज') || m.time.toLowerCase().includes('today'))) return 2;
            if (m.scheduled_at) {
              const diff = new Date(m.scheduled_at).getTime() - Date.now();
              if (diff > 0) return diff;
            }
            return 1000;
          };
          return getScore(a) - getScore(b);
        });

        return (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
            {/* Section Header Row */}
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '10px', marginTop: '2px' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '10px', flexWrap: 'wrap' }}>
                <h2 style={{ margin: 0, fontSize: '20px', fontWeight: 900, color: '#0f172a', letterSpacing: '-0.3px' }}>
                  {isMr ? 'नियोजित बैठका' : 'Scheduled Meetings'}
                </h2>
                <span
                  style={{
                    display: 'inline-flex',
                    alignItems: 'center',
                    gap: '6px',
                    padding: '4px 12px',
                    borderRadius: '9999px',
                    background: '#ecfdf5',
                    border: '1.5px solid #86efac',
                    color: '#15803d',
                    fontSize: '12px',
                    fontWeight: 800,
                    letterSpacing: '0.2px',
                  }}
                >
                  <span
                    style={{
                      width: '7px',
                      height: '7px',
                      borderRadius: '50%',
                      background: '#16a34a',
                      display: 'inline-block',
                      boxShadow: '0 0 6px #16a34a',
                    }}
                  />
                  <span>{isMr ? '(• थेट सक्रिय •)' : '(• Live & Active •)'}</span>
                </span>
              </div>

              {/* Instant Google Meet Launcher */}
              <a
                href="https://meet.google.com/new"
                target="_blank"
                rel="noopener noreferrer"
                title={isMr ? 'नवीन Google Meet सुरू करा' : 'Start Instant Google Meet'}
                style={{
                  display: 'inline-flex',
                  alignItems: 'center',
                  gap: '7px',
                  background: '#f0fdf4',
                  border: '1.5px solid #86efac',
                  color: '#166534',
                  padding: '6px 14px',
                  borderRadius: '10px',
                  fontSize: '12.5px',
                  fontWeight: 800,
                  textDecoration: 'none',
                  boxShadow: '0 2px 6px rgba(22, 163, 74, 0.08)',
                  transition: 'all 0.15s ease',
                }}
              >
                <Video size={15} color="#16a34a" />
                <span>{isMr ? '+ नवीन Google Meet' : '+ New Google Meet'}</span>
              </a>
            </div>

            {/* Meetings Cards Container */}
            <div
              style={{
                display: 'flex',
                flexDirection: 'column',
                gap: '16px',
                background: 'linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%)',
                padding: '16px',
                borderRadius: '24px',
                border: '1.5px solid #e2e8f0',
                boxShadow: 'inset 0 2px 4px rgba(0, 0, 0, 0.02)',
              }}
            >
              {sortedMeetings.map((meeting, index) => {
                const isVirtual = meeting.type === 'Virtual' || (meeting.actionType === 'join');
                const isBooth = meeting.type === 'Booth';
                const isWithin24Hours = meeting.isWithin24Hours || (
                  meeting.time && (
                    meeting.time.includes('उद्या') ||
                    meeting.time.toLowerCase().includes('tomorrow') ||
                    meeting.time.includes('आज') ||
                    meeting.time.toLowerCase().includes('today')
                  )
                );

                // Colorful card backgrounds based on meeting category
                const cardBg = isWithin24Hours
                  ? 'linear-gradient(145deg, #eff6ff 0%, #fef3c7 45%, #ffffff 100%)'
                  : isVirtual
                    ? 'linear-gradient(145deg, #eff6ff 0%, #dbeafe 35%, #ffffff 100%)'
                    : isBooth
                      ? 'linear-gradient(145deg, #f0fdf4 0%, #dcfce7 35%, #ffffff 100%)'
                      : 'linear-gradient(145deg, #fff7ed 0%, #ffedd5 35%, #ffffff 100%)';

                const borderColor = isWithin24Hours
                  ? '#f59e0b'
                  : isVirtual
                    ? '#93c5fd'
                    : isBooth
                      ? '#86efac'
                      : '#fdba74';

                const cardShadow = isWithin24Hours
                  ? '0 8px 24px rgba(245, 158, 11, 0.18), 0 0 0 1.5px #fcd34d'
                  : isVirtual
                    ? '0 6px 20px rgba(37, 99, 235, 0.12)'
                    : isBooth
                      ? '0 6px 20px rgba(22, 163, 74, 0.12)'
                      : '0 6px 20px rgba(234, 88, 12, 0.12)';

                const badgeBg = meeting.badgeBg || (isVirtual ? '#2563eb' : isBooth ? '#16a34a' : '#ea580c');
                const categoryBadge = meeting.categoryBadge || (
                  isVirtual
                    ? (isMr ? '📹 व्हर्च्युअल सभा (Virtual)' : '📹 Virtual Meeting')
                    : isBooth
                      ? (isMr ? '🗓️ बूथ प्रमुख बैठक' : '🗓️ Booth Head Meeting')
                      : (isMr ? '👥 प्रत्यक्ष बैठक (In-person)' : '👥 In-person Meeting')
                );

                return (
                  <div
                    key={meeting.id || index}
                    style={{
                      background: cardBg,
                      border: `1.5px solid ${borderColor}`,
                      borderRadius: '20px',
                      padding: '18px 20px',
                      boxShadow: cardShadow,
                      display: 'flex',
                      flexDirection: 'column',
                      gap: '12px',
                      transition: 'all 0.2s ease',
                      position: 'relative',
                    }}
                  >
                    {/* Card Badges Row */}
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '8px' }}>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '8px', flexWrap: 'wrap' }}>
                        <span
                          style={{
                            display: 'inline-flex',
                            alignItems: 'center',
                            gap: '6px',
                            background: badgeBg,
                            color: '#ffffff',
                            padding: '4px 14px',
                            borderRadius: '9999px',
                            fontSize: '12px',
                            fontWeight: 750,
                            letterSpacing: '0.2px',
                          }}
                        >
                          {categoryBadge}
                        </span>

                        {/* 24-Hour Reminder Badge */}
                        {isWithin24Hours && (
                          <span
                            style={{
                              display: 'inline-flex',
                              alignItems: 'center',
                              gap: '5px',
                              background: '#fef2f2',
                              border: '1.5px solid #fecaca',
                              color: '#dc2626',
                              padding: '3px 10px',
                              borderRadius: '9999px',
                              fontSize: '11px',
                              fontWeight: 850,
                              letterSpacing: '0.2px',
                            }}
                          >
                            <Bell size={12} color="#dc2626" />
                            <span>{isMr ? '🔔 आठवण: २४ तासांत!' : '🔔 Reminder: < 24 hrs'}</span>
                          </span>
                        )}
                      </div>

                      {meeting.statusBadge && (
                        <span
                          style={{
                            display: 'inline-flex',
                            alignItems: 'center',
                            padding: '3px 12px',
                            borderRadius: '9999px',
                            background: isVirtual ? '#ecfdf5' : '#f0fdf4',
                            border: isVirtual ? '1px solid #a7f3d0' : '1px solid #bbf7d0',
                            color: isVirtual ? '#059669' : '#166534',
                            fontSize: '11.5px',
                            fontWeight: 800,
                          }}
                        >
                          {meeting.statusBadge}
                        </span>
                      )}
                    </div>

                    {/* 24-Hour Urgent Reminder Box */}
                    {isWithin24Hours && (
                      <div
                        style={{
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'space-between',
                          flexWrap: 'wrap',
                          gap: '10px',
                          background: 'linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%)',
                          border: '1.5px solid #fcd34d',
                          borderRadius: '12px',
                          padding: '10px 14px',
                          color: '#92400e',
                          boxShadow: '0 2px 6px rgba(217, 119, 6, 0.08)',
                        }}
                      >
                        <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                          <div
                            style={{
                              width: '32px',
                              height: '32px',
                              borderRadius: '10px',
                              background: '#d97706',
                              color: '#ffffff',
                              display: 'flex',
                              alignItems: 'center',
                              justifyContent: 'center',
                              flexShrink: 0,
                            }}
                          >
                            <Bell size={17} />
                          </div>
                          <div>
                            <div style={{ fontWeight: 850, fontSize: '13px', color: '#92400e' }}>
                              {isMr ? 'महत्त्वाची आठवण: ही बैठक पुढील २४ तासांत सुरू होत आहे!' : 'Urgent Reminder: This meeting starts within 24 hours!'}
                            </div>
                            <div style={{ fontSize: '11.5px', color: '#b45309', fontWeight: 600, marginTop: '1px' }}>
                              {isMr ? 'वेळ व लिंकची खात्री करा आणि सदस्यांना पूर्वकल्पना द्या.' : 'Confirm time and link to ensure prompt attendance.'}
                            </div>
                          </div>
                        </div>

                        <a
                          href={`https://api.whatsapp.com/send?text=${encodeURIComponent(
                            (isMr
                              ? `🔔 *बैठक आठवण (Meeting Reminder)*: उद्या आपली '${meeting.title}' बैठक नियोजित आहे.\n⏰ वेळ: ${meeting.time}\n📍 स्थान/लिंक: ${meeting.link || meeting.location}\nकृपया वेळेवर उपस्थित राहावे.`
                              : `🔔 *Meeting Reminder*: Meeting '${meeting.title}' is scheduled within 24 hours.\n⏰ Time: ${meeting.time}\n📍 Location: ${meeting.link || meeting.location}\nPlease attend on time.`)
                          )}`}
                          target="_blank"
                          rel="noopener noreferrer"
                          title={isMr ? 'WhatsApp वर आठवण मेसेज पाठवा' : 'Send WhatsApp Reminder'}
                          style={{
                            display: 'inline-flex',
                            alignItems: 'center',
                            gap: '6px',
                            background: '#16a34a',
                            color: '#ffffff',
                            padding: '6px 12px',
                            borderRadius: '8px',
                            fontSize: '11.5px',
                            fontWeight: 800,
                            textDecoration: 'none',
                            boxShadow: '0 2px 6px rgba(22, 163, 74, 0.25)',
                            transition: 'all 0.15s ease',
                          }}
                        >
                          <span>💬</span>
                          <span>{isMr ? 'WhatsApp आठवण पाठवा' : 'Send WhatsApp Reminder'}</span>
                        </a>
                      </div>
                    )}

                    {/* Card Title */}
                    <h3
                      style={{
                        margin: 0,
                        fontSize: '17px',
                        fontWeight: 850,
                        color: '#0f172a',
                        lineHeight: 1.35,
                        display: 'flex',
                        alignItems: 'center',
                        gap: '6px',
                        flexWrap: 'wrap',
                      }}
                    >
                      <span>{meeting.title}</span>
                      {meeting.showVideoEmoji && <span style={{ fontSize: '15px' }}>📹</span>}
                    </h3>

                    {/* Meeting Details in frosted glass container */}
                    <div
                      style={{
                        background: 'rgba(255, 255, 255, 0.84)',
                        backdropFilter: 'blur(8px)',
                        borderRadius: '14px',
                        padding: '12px 14px',
                        border: '1px solid rgba(255, 255, 255, 0.95)',
                        boxShadow: '0 2px 8px rgba(0, 0, 0, 0.02)',
                        display: 'flex',
                        flexDirection: 'column',
                        gap: '8px',
                      }}
                    >
                      {meeting.combinedMeta ? (
                        /* Compact Row for Booth style: Location • Time */
                        <div style={{ display: 'flex', alignItems: 'center', flexWrap: 'wrap', gap: '14px', color: '#475569', fontSize: '13px', fontWeight: 650 }}>
                          <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                            <MapPin size={15} color="#ea580c" />
                            <span>{meeting.location}</span>
                          </div>
                          <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                            <Clock size={15} color="#0284c7" />
                            <span>{meeting.time}</span>
                          </div>
                        </div>
                      ) : (
                        /* 2 Row Format: Clock/Calendar & Location/Virtual */
                        <>
                          <div style={{ display: 'flex', alignItems: 'center', gap: '8px', color: '#334155', fontSize: '13px', fontWeight: 650 }}>
                            {meeting.timeIcon === 'calendar' ? (
                              <Calendar size={15} color="#0284c7" />
                            ) : (
                              <Clock size={15} color="#0284c7" />
                            )}
                            <span>{meeting.time}</span>
                          </div>

                          {meeting.locationIcon === 'virtual' || isVirtual ? (
                            <div style={{ display: 'flex', alignItems: 'center', gap: '8px', flexWrap: 'wrap', background: '#eff6ff', padding: '6px 12px', borderRadius: '10px', border: '1px solid #bfdbfe' }}>
                              <div style={{ display: 'flex', alignItems: 'center', gap: '6px', color: '#1d4ed8', fontSize: '12.5px', fontWeight: 800 }}>
                                <Video size={15} color="#2563eb" />
                                <span>Google Meet:</span>
                              </div>
                              <a
                                href={meeting.link || 'https://meet.google.com/pqr-stuv-wxy'}
                                target="_blank"
                                rel="noopener noreferrer"
                                style={{
                                  fontSize: '12.5px',
                                  color: '#2563eb',
                                  fontWeight: 750,
                                  textDecoration: 'none',
                                  display: 'inline-flex',
                                  alignItems: 'center',
                                  gap: '4px',
                                }}
                              >
                                <span>{meeting.link ? meeting.link.replace(/^https?:\/\//, '') : 'meet.google.com/pqr-stuv-wxy'}</span>
                                <ExternalLink size={12} />
                              </a>
                            </div>
                          ) : (
                            <div style={{ display: 'flex', alignItems: 'center', gap: '8px', color: '#475569', fontSize: '13px', fontWeight: 600 }}>
                              <MapPin size={15} color="#ea580c" />
                              <span>{meeting.location}</span>
                            </div>
                          )}
                        </>
                      )}
                    </div>

                    {/* Bottom Action Row (NO "उपस्थित कार्यकर्ते" / NO attendee avatars as requested!) */}
                    <div
                      style={{
                        display: 'flex',
                        justifyContent: 'flex-end',
                        alignItems: 'center',
                        paddingTop: '10px',
                        borderTop: '1px solid rgba(15, 23, 42, 0.07)',
                        marginTop: '2px',
                        gap: '8px',
                        flexWrap: 'wrap',
                      }}
                    >
                      {meeting.actionType === 'join' || isVirtual ? (
                        <div style={{ display: 'flex', alignItems: 'center', gap: '8px', flexWrap: 'wrap' }}>
                          <button
                            type="button"
                            onClick={() => {
                              const meetUrl = meeting.link || 'https://meet.google.com/pqr-stuv-wxy';
                              navigator.clipboard.writeText(meetUrl);
                              setCopiedMeetLink(meeting.id || true);
                              setTimeout(() => setCopiedMeetLink(false), 2000);
                            }}
                            title={isMr ? 'Google Meet लिंक कॉपी करा' : 'Copy Google Meet Link'}
                            style={{
                              display: 'inline-flex',
                              alignItems: 'center',
                              gap: '6px',
                              padding: '8px 14px',
                              borderRadius: '12px',
                              border: copiedMeetLink === (meeting.id || true) ? '1.5px solid #86efac' : '1.5px solid #cbd5e1',
                              background: copiedMeetLink === (meeting.id || true) ? '#dcfce7' : '#ffffff',
                              color: copiedMeetLink === (meeting.id || true) ? '#15803d' : '#475569',
                              fontSize: '12.5px',
                              fontWeight: 750,
                              cursor: 'pointer',
                              transition: 'all 0.15s ease',
                            }}
                          >
                            {copiedMeetLink === (meeting.id || true) ? <Check size={14} /> : <Copy size={14} />}
                            <span>{copiedMeetLink === (meeting.id || true) ? (isMr ? '✓ कॉपी झाली!' : '✓ Copied!') : (isMr ? 'लिंक कॉपी' : 'Copy Link')}</span>
                          </button>

                          <a
                            href={meeting.link || 'https://meet.google.com/pqr-stuv-wxy'}
                            target="_blank"
                            rel="noopener noreferrer"
                            style={{
                              display: 'inline-flex',
                              alignItems: 'center',
                              justifyContent: 'center',
                              gap: '7px',
                              background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
                              color: '#ffffff',
                              padding: '8px 22px',
                              borderRadius: '12px',
                              fontSize: '13px',
                              fontWeight: 800,
                              textDecoration: 'none',
                              boxShadow: '0 4px 14px rgba(37,99,235,0.3)',
                              transition: 'all 0.15s ease',
                            }}
                          >
                            <Video size={15} />
                            <span>{isMr ? 'Google Meet जॉईन करा' : 'Join Google Meet'}</span>
                          </a>
                        </div>
                      ) : (
                        <a
                          href={`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(meeting.location + ' Sangli')}`}
                          target="_blank"
                          rel="noopener noreferrer"
                          style={{
                            display: 'inline-flex',
                            alignItems: 'center',
                            gap: '7px',
                            padding: '7px 18px',
                            borderRadius: '9999px',
                            border: '1.5px solid #cbd5e1',
                            background: '#ffffff',
                            color: '#1e293b',
                            fontSize: '12.5px',
                            fontWeight: 750,
                            textDecoration: 'none',
                            boxShadow: '0 2px 4px rgba(0,0,0,0.03)',
                            transition: 'all 0.15s ease',
                          }}
                        >
                          <span style={{ fontSize: '13px' }}>🗺️</span>
                          <span>{meeting.actionLabel || (isMr ? 'नकाशा / दिशा' : 'Map / Directions')}</span>
                        </a>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        );
      })()}
    </div>
  );
}
