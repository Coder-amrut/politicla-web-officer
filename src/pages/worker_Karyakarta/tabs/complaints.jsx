import React, { useState, useRef, useEffect } from 'react';
import {
  AlertCircle,
  Plus,
  Search,
  AlertTriangle,
  Check,
  RotateCw,
  Clock,
  Phone,
  CheckCircle2,
  X,
  ChevronDown,
  ArrowLeft
} from 'lucide-react';

const marathiDigitsMap = ['०', '१', '२', '३', '४', '५', '६', '७', '८', '९'];

export const toMarathiDigits = (val) => {
  if (val === null || val === undefined) return '';
  return String(val).replace(/[0-9]/g, (d) => marathiDigitsMap[Number(d)]);
};

export const formatComplaintDate = (dateVal, isMr = true) => {
  if (!dateVal) return isMr ? 'आज' : 'Today';
  if (typeof dateVal === 'string' && (dateVal.includes('ऑक्टोबर') || dateVal.includes('जानेवारी'))) {
    return dateVal;
  }

  try {
    const d = new Date(dateVal);
    if (isNaN(d.getTime())) return String(dateVal);

    if (!isMr) {
      return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    const monthsMr = [
      'जानेवारी', 'फेब्रुवारी', 'मार्च', 'एप्रिल', 'मे', 'जून',
      'जुलै', 'ऑगस्ट', 'सप्टेंबर', 'ऑक्टोबर', 'नोव्हेंबर', 'डिसेंबर'
    ];
    const day = String(d.getDate()).padStart(2, '0');
    const month = monthsMr[d.getMonth()];
    const year = d.getFullYear();
    return `${toMarathiDigits(day)} ${month} ${toMarathiDigits(year)}`;
  } catch {
    return String(dateVal);
  }
};

// Properly contained custom dropdown for modal fields (Category & Urgency)
const CustomSelect = ({ value, onChange, options, placeholder }) => {
  const [open, setOpen] = useState(false);
  const containerRef = useRef(null);

  useEffect(() => {
    const handleClickOutside = (e) => {
      if (containerRef.current && !containerRef.current.contains(e.target)) {
        setOpen(false);
      }
    };
    if (open) {
      document.addEventListener('mousedown', handleClickOutside);
      document.addEventListener('touchstart', handleClickOutside);
    }
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
      document.removeEventListener('touchstart', handleClickOutside);
    };
  }, [open]);

  const selectedOption = options.find((opt) => opt.value === value);

  return (
    <div className="takrari-custom-select-container" ref={containerRef}>
      <button
        type="button"
        className={`takrari-custom-select-trigger ${open ? 'open' : ''}`}
        onClick={() => setOpen((prev) => !prev)}
      >
        <span className="takrari-custom-select-value">
          {selectedOption ? selectedOption.label : placeholder}
        </span>
        <ChevronDown
          size={16}
          className={`takrari-custom-select-arrow ${open ? 'open' : ''}`}
        />
      </button>

      {open && (
        <div className="takrari-custom-select-menu">
          {options.map((opt) => {
            const isSelected = opt.value === value;
            return (
              <div
                key={opt.value}
                className={`takrari-custom-select-item ${isSelected ? 'selected' : ''}`}
                onClick={() => {
                  onChange(opt.value);
                  setOpen(false);
                }}
              >
                <span className="takrari-custom-select-item-text">{opt.label}</span>
                {isSelected && <Check size={15} className="takrari-custom-select-check" />}
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
};

// Properly contained custom dropdown for grievance card status
const CardStatusSelect = ({ status, onChange, isMr }) => {
  const [open, setOpen] = useState(false);
  const containerRef = useRef(null);

  useEffect(() => {
    const handleClickOutside = (e) => {
      if (containerRef.current && !containerRef.current.contains(e.target)) {
        setOpen(false);
      }
    };
    if (open) {
      document.addEventListener('mousedown', handleClickOutside);
      document.addEventListener('touchstart', handleClickOutside);
    }
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
      document.removeEventListener('touchstart', handleClickOutside);
    };
  }, [open]);

  const statusOptions = [
    { value: 'Pending', label: isMr ? 'प्रलंबित' : 'Pending', dot: '#eab308' },
    { value: 'In Progress', label: isMr ? 'सुरू आहे' : 'In Progress', dot: '#0284c7' },
    { value: 'Resolved', label: isMr ? 'निवारण झाले' : 'Resolved', dot: '#16a34a' },
  ];

  const currentOpt = statusOptions.find((o) => o.value === status) || statusOptions[0];

  return (
    <div className={`takrari-card-status-wrapper ${open ? 'dropdown-open' : ''}`} ref={containerRef}>
      <button
        type="button"
        className={`takrari-status-dropdown-btn takrari-status-btn-${(status || 'Pending').toLowerCase().replace(' ', '-')}`}
        onClick={() => setOpen((prev) => !prev)}
        title={isMr ? "स्थिती निवडा" : "Change status"}
      >
        <span className="takrari-status-dot" style={{ backgroundColor: currentOpt.dot }} />
        <span>{currentOpt.label}</span>
        <ChevronDown size={14} className={`takrari-status-chevron ${open ? 'open' : ''}`} />
      </button>

      {open && (
        <div className="takrari-card-status-menu">
          {statusOptions.map((opt) => {
            const isSelected = opt.value === status;
            return (
              <button
                key={opt.value}
                type="button"
                className={`takrari-card-status-item ${isSelected ? 'active' : ''}`}
                onClick={() => {
                  onChange(opt.value);
                  setOpen(false);
                }}
              >
                <div style={{ display: 'flex', alignItems: 'center', gap: '7px' }}>
                  <span className="takrari-status-dot" style={{ backgroundColor: opt.dot }} />
                  <span>{opt.label}</span>
                </div>
                {isSelected && <Check size={14} color="#ea580c" />}
              </button>
            );
          })}
        </div>
      )}
    </div>
  );
};

export default function ComplaintsTab({
  isMr = true,
  t,
  complaintsData = [],
  complaintFilter = 'All',
  setComplaintFilter,
  complaintSearchQuery = '',
  setComplaintSearchQuery,
  handleUpdateComplaintStatus,
  isAddingComplaint,
  setIsAddingComplaint,
  complaintFormData,
  setComplaintFormData,
  checkDuplicateField,
  complaintDuplicateWarning,
  setComplaintDuplicateWarning,
  handleComplaintSubmit,
  currentCoords,
  submittingComplaint,
}) {
  const filteredComplaints = complaintsData.filter((c) => {
    const matchesStatus = complaintFilter === 'All' || c.status === complaintFilter;
    if (!matchesStatus) return false;
    if (!complaintSearchQuery.trim()) return true;
    const q = complaintSearchQuery.toLowerCase();
    return (
      (c.name && c.name.toLowerCase().includes(q)) ||
      (c.phone && c.phone.includes(q)) ||
      (c.category && c.category.toLowerCase().includes(q)) ||
      (c.complaint_code && c.complaint_code.toLowerCase().includes(q)) ||
      (c.desc && c.desc.toLowerCase().includes(q))
    );
  });

  const totalCount = complaintsData.length;
  const pendingCount = complaintsData.filter((c) => c.status === 'Pending').length;
  const inProgressCount = complaintsData.filter((c) => c.status === 'In Progress').length;
  const resolvedCount = complaintsData.filter((c) => c.status === 'Resolved' || c.status === 'Action Taken').length;

  const filterTabs = [
    {
      key: 'All',
      label: isMr ? 'सर्व तक्रारी' : 'All Grievances',
      count: isMr ? toMarathiDigits(totalCount) : totalCount,
      bg: '#d94814',
    },
    {
      key: 'Pending',
      label: isMr ? 'प्रलंबित' : 'Pending',
      count: isMr ? toMarathiDigits(pendingCount) : pendingCount,
      bg: '#d97706',
    },
    {
      key: 'In Progress',
      label: isMr ? 'सुरू आहे' : 'In Progress',
      count: isMr ? toMarathiDigits(inProgressCount) : inProgressCount,
      bg: '#0284c7',
    },
    {
      key: 'Resolved',
      label: isMr ? 'निवारण झाले' : 'Resolved',
      count: isMr ? toMarathiDigits(resolvedCount) : resolvedCount,
      bg: '#15803d',
    },
  ];

  // Confirmation state for Save & Cancel popups ('save' | 'cancel' | null)
  const [confirmComplaintAction, setConfirmComplaintAction] = useState(null);

  const handleRequestComplaintSave = (e) => {
    if (e) e.preventDefault();
    if (!complaintFormData.name?.trim()) {
      alert(isMr ? 'कृपया नागरिकाचे पूर्ण नाव भरा.' : 'Please enter citizen name.');
      return;
    }
    if (!complaintFormData.desc?.trim()) {
      alert(isMr ? 'कृपया समस्येचे सविस्तर वर्णन भरा.' : 'Please enter problem description.');
      return;
    }
    setConfirmComplaintAction('save');
  };

  const handleConfirmComplaintSave = (e) => {
    setConfirmComplaintAction(null);
    handleComplaintSubmit(e);
  };

  const handleConfirmComplaintCancel = () => {
    setConfirmComplaintAction(null);
    setIsAddingComplaint(false);
  };

  const renderConfirmationModal = () => {
    if (!confirmComplaintAction) return null;
    return (
      <div
        style={{
          position: 'fixed',
          inset: 0,
          background: 'rgba(15, 23, 42, 0.65)',
          backdropFilter: 'blur(5px)',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          zIndex: 10005,
          padding: '16px',
          animation: 'fadeIn 0.15s ease-out',
        }}
        onClick={() => setConfirmComplaintAction(null)}
      >
        <div
          style={{
            background: '#ffffff',
            borderRadius: '18px',
            maxWidth: '450px',
            width: '100%',
            padding: '24px 22px',
            boxShadow: '0 20px 45px -10px rgba(0, 0, 0, 0.4)',
            border: '1.5px solid #e2e8f0',
            textAlign: 'center',
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center',
            gap: '14px',
          }}
          onClick={(e) => e.stopPropagation()}
        >
          {/* Popup Icon */}
          <div
            style={{
              width: '58px',
              height: '58px',
              borderRadius: '50%',
              background: confirmComplaintAction === 'save' ? '#dcfce7' : '#fee2e2',
              border: `2.5px solid ${confirmComplaintAction === 'save' ? '#86efac' : '#fca5a5'}`,
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              fontSize: '26px',
            }}
          >
            {confirmComplaintAction === 'save' ? '📋' : '⚠️'}
          </div>

          {/* Popup Title */}
          <h3 style={{ margin: 0, fontSize: '18px', fontWeight: 850, color: '#0f172a' }}>
            {confirmComplaintAction === 'save'
              ? (isMr ? 'खात्री करा: तक्रार नोंदवायची आहे का?' : 'Confirm: Submit Grievance?')
              : (isMr ? 'खात्री करा: तक्रार नोंदणी रद्द करायची आहे का?' : 'Confirm: Cancel Grievance Entry?')}
          </h3>

          {/* Popup Description */}
          <div style={{ fontSize: '13.5px', color: '#475569', lineHeight: 1.55 }}>
            {confirmComplaintAction === 'save' ? (
              isMr ? (
                <>
                  नागरिकाचे नाव: <strong>{complaintFormData.name}</strong>
                  {complaintFormData.phone ? <> • फोन: <strong>{complaintFormData.phone}</strong></> : null}
                  <br />
                  प्रवर्ग: <strong>{complaintFormData.category}</strong>
                  <br />
                  <span style={{ color: '#15803d', fontWeight: 700, display: 'inline-block', marginTop: '6px' }}>
                    तुम्ही भरलेली सर्व माहिती बरोबर आहे का? ही तक्रार त्वरित नोंदवली जाईल.
                  </span>
                </>
              ) : (
                <>
                  Citizen: <strong>{complaintFormData.name}</strong>
                  {complaintFormData.phone ? <> • Phone: <strong>{complaintFormData.phone}</strong></> : null}
                  <br />
                  Category: <strong>{complaintFormData.category}</strong>
                  <br />
                  <span style={{ color: '#15803d', fontWeight: 700, display: 'inline-block', marginTop: '6px' }}>
                    Are you sure this grievance detail is correct and ready to register?
                  </span>
                </>
              )
            ) : (
              isMr ? (
                <>
                  तुम्ही तक्रार नोंदणी रद्द केल्यास भरलेली सर्व माहिती निघून जाईल.
                  <br />
                  <span style={{ color: '#b91c1c', fontWeight: 700, display: 'inline-block', marginTop: '6px' }}>
                    तुम्ही नक्की रद्द करू इच्छिता का?
                  </span>
                </>
              ) : (
                <>
                  Any entered details will be discarded and you will return to complaints list.
                  <br />
                  <span style={{ color: '#b91c1c', fontWeight: 700, display: 'inline-block', marginTop: '6px' }}>
                    Are you sure you want to cancel?
                  </span>
                </>
              )
            )}
          </div>

          {/* Action Buttons */}
          <div style={{ display: 'flex', gap: '12px', width: '100%', marginTop: '6px' }}>
            <button
              type="button"
              onClick={() => setConfirmComplaintAction(null)}
              style={{
                flex: 1,
                padding: '11px 16px',
                borderRadius: '10px',
                border: '1.5px solid #cbd5e1',
                background: '#f8fafc',
                color: '#334155',
                fontWeight: 750,
                fontSize: '13.5px',
                cursor: 'pointer',
                transition: 'all 0.15s ease',
              }}
            >
              {confirmComplaintAction === 'save'
                ? (isMr ? 'मागे जा / तपासा' : 'Review / Go Back')
                : (isMr ? 'नोंद सुरू ठेवा' : 'Continue')}
            </button>

            <button
              type="button"
              onClick={confirmComplaintAction === 'save' ? handleConfirmComplaintSave : handleConfirmComplaintCancel}
              style={{
                flex: 1,
                padding: '11px 16px',
                borderRadius: '10px',
                border: 'none',
                background: confirmComplaintAction === 'save'
                  ? 'linear-gradient(135deg, #16a34a 0%, #15803d 100%)'
                  : 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
                color: '#ffffff',
                fontWeight: 800,
                fontSize: '13.5px',
                cursor: 'pointer',
                boxShadow: confirmComplaintAction === 'save'
                  ? '0 4px 14px rgba(22, 163, 74, 0.35)'
                  : '0 4px 14px rgba(239, 68, 68, 0.35)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                gap: '6px',
              }}
            >
              {confirmComplaintAction === 'save'
                ? (isMr ? '✓ होय, नोंदवा' : '✓ Yes, Register')
                : (isMr ? '✕ होय, रद्द करा' : '✕ Yes, Cancel')}
            </button>
          </div>
        </div>
      </div>
    );
  };

  // If user clicked "नवीन समस्या नोंदवा", render the Full-Page Form (no popup modal)
  if (isAddingComplaint) {
    return (
      <div className="takrari-wrapper">
        {/* Full-Page Form Header with Back Button */}
        <div className="takrari-form-page-header">
          <button
            type="button"
            onClick={() => setConfirmComplaintAction('cancel')}
            className="takrari-back-btn"
            title={isMr ? "मागे जा" : "Go Back"}
          >
            <ArrowLeft size={18} />
            <span>{isMr ? 'मागे जा' : 'Back'}</span>
          </button>

          <div className="takrari-form-header-title-box">
            <div className="takrari-modal-bang">!</div>
            <div>
              <h2 className="takrari-form-page-title">
                {isMr ? 'नवीन समस्या नोंदवा' : 'Register New Grievance'}
              </h2>
              <p className="takrari-form-page-subtitle">
                {isMr ? 'नागरिकाची तक्रार, ठिकाण आणि आवश्यक तपशील भरा' : 'Fill citizen complaint details, urgency, and description'}
              </p>
            </div>
          </div>
        </div>

        {/* Full-Page Form Card Container */}
        <div className="takrari-form-page-card">
          <form onSubmit={handleRequestComplaintSave} className="takrari-modal-form">
            {/* Field 1: Name */}
            <div className="takrari-form-group">
              <label className="takrari-form-label">
                {isMr ? 'नागरिकाचे पूर्ण नाव *' : 'Citizen Full Name *'}
              </label>
              <input
                type="text"
                required
                placeholder={isMr ? "नागरिकाचे नाव प्रविष्ट करा" : "Enter citizen name"}
                value={complaintFormData.name}
                onChange={(e) => setComplaintFormData({ ...complaintFormData, name: e.target.value })}
                className="takrari-form-input"
              />
            </div>

            {/* Field 2: Phone */}
            <div className="takrari-form-group">
              <label className="takrari-form-label">
                {isMr ? 'फोन / मोबाईल नंबर' : 'Phone / Mobile Number'}
              </label>
              <input
                type="tel"
                maxLength="10"
                placeholder="98XXXXXXXX"
                value={complaintFormData.phone}
                onChange={(e) => {
                  const val = e.target.value.replace(/\D/g, '');
                  setComplaintFormData({ ...complaintFormData, phone: val });
                  if (val.length >= 10 && checkDuplicateField) {
                    checkDuplicateField('phone', val, setComplaintDuplicateWarning);
                  } else if (setComplaintDuplicateWarning) {
                    setComplaintDuplicateWarning(null);
                  }
                }}
                className="takrari-form-input"
              />
              {complaintDuplicateWarning && (
                <div className="takrari-form-warning">
                  <AlertTriangle size={13} style={{ flexShrink: 0 }} />
                  <span>⚠️ {complaintDuplicateWarning}</span>
                </div>
              )}
            </div>

            {/* Row for Category & Priority */}
            <div className="takrari-form-row-2">
              {/* Field 3: Category */}
              <div className="takrari-form-group">
                <label className="takrari-form-label">
                  {isMr ? 'समस्येचा वर्ग *' : 'Grievance Category *'}
                </label>
                <CustomSelect
                  value={complaintFormData.category}
                  onChange={(val) => setComplaintFormData({ ...complaintFormData, category: val })}
                  options={[
                    { value: 'पाणी पुरवठा (Water Supply)', label: isMr ? '💧 पाणी पुरवठा' : '💧 Water Supply' },
                    { value: 'स्ट्रीट लाईट (Street Light)', label: isMr ? '💡 स्ट्रीट लाईट' : '💡 Street Lights' },
                    { value: 'कचरा व्यवस्थापन (Garbage Management)', label: isMr ? '🧹 कचरा व्यवस्थापन' : '🧹 Garbage & Sanitation' },
                    { value: 'रस्ता डांबरीकरण (Road Repair)', label: isMr ? '🛣️ रस्ता डांबरीकरण' : '🛣️ Roads & Repair' },
                    { value: 'आरोग्य व रुग्णालय (Healthcare)', label: isMr ? '🏥 आरोग्य / रुग्णालय' : '🏥 Healthcare' },
                    { value: 'शासकीय योजना (Schemes)', label: isMr ? '📄 शासकीय योजना' : '📄 Government Schemes' },
                    { value: 'इतर समस्या (Others)', label: isMr ? '📌 इतर समस्या (Others)' : '📌 Other Civic Issues' },
                  ]}
                  placeholder={isMr ? "समस्येचा वर्ग निवडा" : "Select Category"}
                />
              </div>

              {/* Field 4: Urgency */}
              <div className="takrari-form-group">
                <label className="takrari-form-label">
                  {isMr ? 'तातडीची पातळी' : 'Priority Level'}
                </label>
                <CustomSelect
                  value={complaintFormData.urgency}
                  onChange={(val) => setComplaintFormData({ ...complaintFormData, urgency: val })}
                  options={[
                    { value: 'सामान्य (Normal)', label: isMr ? '🟢 सामान्य' : '🟢 Normal' },
                    { value: 'मध्यम (Medium)', label: isMr ? '🟡 मध्यम (३ दिवस)' : '🟡 Medium (3 Days)' },
                    { value: 'तातडीचे (Urgent)', label: isMr ? '🔴 तातडीचे (२४ तास)' : '🔴 Urgent (24 Hours)' },
                  ]}
                  placeholder={isMr ? "पातळी निवडा" : "Select Priority"}
                />
              </div>
            </div>

            {/* Field 5: Description */}
            <div className="takrari-form-group">
              <label className="takrari-form-label">
                {isMr ? 'समस्येचे सविस्तर वर्णन *' : 'Detailed Description *'}
              </label>
              <textarea
                rows="4"
                required
                placeholder={isMr ? "समस्येचा तपशील, विशिष्ट ठिकाण व नागरिकांची मागणी लिहा..." : "Describe the problem, specific location..."}
                value={complaintFormData.desc}
                onChange={(e) => setComplaintFormData({ ...complaintFormData, desc: e.target.value })}
                className="takrari-form-textarea"
              />
            </div>

            {/* Field 6: Photo / Attachment */}
            <div className="takrari-form-group">
              <label className="takrari-form-label">
                📷 {isMr ? 'समस्येचा फोटो / कागदपत्र जोडा' : 'Attach Photo or Document'}
              </label>
              <input
                type="file"
                accept="image/*,.pdf,.doc,.docx"
                onChange={(e) => {
                  const file = e.target.files?.[0];
                  if (file) {
                    setComplaintFormData({ ...complaintFormData, photo: file.name });
                  }
                }}
                className="takrari-file-input"
              />
            </div>

            {/* Form Action Buttons */}
            <div className="takrari-modal-actions" style={{ marginTop: '18px' }}>
              <button
                type="button"
                onClick={() => setConfirmComplaintAction('cancel')}
                disabled={submittingComplaint}
                className="takrari-btn-cancel"
              >
                {isMr ? 'रद्द करा' : 'Cancel'}
              </button>

              <button
                type="submit"
                disabled={submittingComplaint}
                className="takrari-btn-submit"
              >
                <span>
                  {submittingComplaint
                    ? (isMr ? 'नोंद होत आहे...' : 'Registering...')
                    : (isMr ? '✓ तक्रार नोंदवा' : '✓ Register Grievance')}
                </span>
              </button>
            </div>
          </form>
        </div>

        {/* Confirmation Modal */}
        {renderConfirmationModal()}
      </div>
    );
  }

  return (
    <div className="takrari-wrapper">
      {/* 1. TOP HEADER BANNER (Navy Gradient with Rainbow Ring & Glowing Button) */}
      <div className="takrari-header-banner">
        <div className="takrari-header-top">
          <div className="takrari-rainbow-ring">
            <div className="takrari-rainbow-inner">
              <span className="takrari-bang-mark">!</span>
            </div>
          </div>
          <div className="takrari-titles-wrap">
            <h1 className="takrari-main-title">
              {isMr ? 'नागरिक तक्रारी आणि सेवा' : 'Citizen Grievances & Services'}
            </h1>
            <p className="takrari-main-subtitle">
              {isMr ? 'समस्या नोंदवा, मागोवा घ्या आणि जलद निवारण करा' : 'Report issues, track progress, and ensure fast resolution'}
            </p>
          </div>
        </div>

        {/* Orange Gradient CTA Button */}
        <button
          type="button"
          className="takrari-cta-btn"
          onClick={() => setIsAddingComplaint(true)}
        >
          <Plus size={20} strokeWidth={2.8} />
          <span>{isMr ? 'नवीन तक्रार नोंदवा' : 'Register New Grievance'}</span>
        </button>
      </div>

      {/* Filter Pills Bar */}
      <div className="takrari-filters-row">
        {filterTabs.map((tab) => {
          const isSelected = complaintFilter === tab.key;
          return (
            <button
              key={tab.key}
              type="button"
              onClick={() => setComplaintFilter(tab.key)}
              className={`takrari-filter-pill ${isSelected ? 'active' : ''}`}
              style={{
                backgroundColor: tab.bg,
              }}
            >
              <span className="takrari-pill-text">{tab.label}</span>
              <span className="takrari-pill-badge">{tab.count}</span>
            </button>
          );
        })}
      </div>

      {/* Search Input Bar */}
      <div className="takrari-search-box">
        <Search size={18} color="#94a3b8" className="takrari-search-svg" />
        <input
          type="text"
          className="takrari-search-field"
          placeholder={isMr ? "तक्रार, नाव किंवा समस्येचा शोध घ्या..." : "Search complaint, citizen name or issue..."}
          value={complaintSearchQuery}
          onChange={(e) => setComplaintSearchQuery(e.target.value)}
        />
        {complaintSearchQuery && (
          <button
            type="button"
            className="takrari-search-clear"
            onClick={() => setComplaintSearchQuery('')}
            title="Clear search"
          >
            ✕
          </button>
        )}
      </div>

      {/* Complaints Cards List */}
      <div className="takrari-cards-container">
        {filteredComplaints.length > 0 ? (
          filteredComplaints.map((c) => {
            const isResolved = c.status === 'Resolved' || c.status === 'Action Taken';
            const isInProgress = c.status === 'In Progress';
            const isPending = c.status === 'Pending' || !c.status;
            const statusType = isResolved ? 'resolved' : isInProgress ? 'progress' : 'pending';

            const phoneDisplay = c.phone || '';
            const cleanPhone = phoneDisplay.replace(/\D/g, '');
            const formattedPhone = isMr ? toMarathiDigits(phoneDisplay) : phoneDisplay;

            return (
              <div key={c.id || c.complaint_code} className={`takrari-card takrari-card-${statusType}`}>
                {/* Left Accent Stripe */}
                <div className={`takrari-card-accent takrari-accent-${statusType}`} />

                <div className="takrari-card-content">
                  {/* Header Row: ID and Status Badge */}
                  <div className="takrari-card-top-bar">
                    <span className="takrari-card-code-text">
                      {c.complaint_code || `C-${c.id}`}
                    </span>

                    <button
                      type="button"
                      className={`takrari-status-btn takrari-status-btn-${statusType}`}
                      onClick={() => {
                        const nextStatus = isPending ? 'In Progress' : isInProgress ? 'Resolved' : 'Pending';
                        handleUpdateComplaintStatus(c.id, nextStatus);
                      }}
                      title={isMr ? "स्थिती बदलण्यासाठी क्लिक करा" : "Click to update status"}
                    >
                      {isResolved && (
                        <>
                          <Check size={14} strokeWidth={3} />
                          <span>{isMr ? 'निवारण झाले' : 'Resolved'}</span>
                        </>
                      )}
                      {isInProgress && (
                        <>
                          <RotateCw size={13} strokeWidth={2.8} />
                          <span>{isMr ? 'सुरू आहे' : 'In Progress'}</span>
                        </>
                      )}
                      {isPending && (
                        <>
                          <Clock size={13} strokeWidth={2.8} />
                          <span>{isMr ? 'प्रलंबित' : 'Pending'}</span>
                        </>
                      )}
                    </button>
                  </div>

                  {/* Name and Category Badge Row */}
                  <div className="takrari-name-badge-row">
                    <h3 className="takrari-citizen-name">{c.name}</h3>
                    {c.category && (
                      <span className="takrari-category-tag">
                        {c.category.replace(/\(.*?\)/g, '').trim()}
                      </span>
                    )}
                  </div>

                  {/* Phone Row */}
                  {phoneDisplay && (
                    <div className="takrari-phone-row">
                      <a href={`tel:${cleanPhone}`} className="takrari-phone-action">
                        <Phone size={14} color="#e11d48" strokeWidth={2.5} />
                        <span>{formattedPhone}</span>
                      </a>
                    </div>
                  )}

                  {/* Description */}
                  <p className="takrari-description-text">
                    {c.desc}
                  </p>

                  {/* Footer Row: Date and Status Dropdown */}
                  <div className="takrari-card-bottom-row">
                    <span className="takrari-date-label">
                      {formatComplaintDate(c.date || c.date_reported, isMr)}
                    </span>

                    <CardStatusSelect
                      status={c.status}
                      onChange={(newStatus) => handleUpdateComplaintStatus(c.id, newStatus)}
                      isMr={isMr}
                    />
                  </div>
                </div>
              </div>
            );
          })
        ) : (
          <div className="takrari-no-records">
            <AlertCircle size={38} color="#94a3b8" />
            <p>{isMr ? 'कोणतीही तक्रार आढळली नाही.' : 'No grievances found matching criteria.'}</p>
          </div>
        )}
      </div>
    </div>
  );
}
