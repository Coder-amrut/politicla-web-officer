import React, { useState } from 'react';
import {
  ClipboardCheck,
  Shield,
  CheckCircle2,
  Save,
  Target,
  Clock,
  Plus,
  Trash2,
  AlertTriangle,
  Lock,
  ClipboardList,
  ArrowLeft
} from 'lucide-react';

export default function SurveyTab({
  isMr,
  language = 'en',
  t,
  surveysList = [],
  surveyStats = { allocatedForms: 0, savedToBackend: 0, totalTarget: 0, pendingTarget: 0 },
  savedSubmissionsList = [],
  surveyActiveView = 'forms',
  setSurveyActiveView,
  activeSurveyToFill,
  setActiveSurveyToFill,
  surveyFormData,
  setSurveyFormData,
  handleSurveySubmit,
  handleWorkerAddMember,
  handleWorkerRemoveMember,
  handleWorkerMemberChange,
  handleTotalMatdarChange,
  handleCustomAnswerChange,
  handleMultiChoiceToggle,
  handleOpenSurveyModal,
  submittingSurvey,
  checkDuplicateField,
  surveyDuplicateWarning,
  setSurveyDuplicateWarning,
}) {
  const isMarathi = language === 'mr' || isMr;
  const activeSurveys = surveysList;

  // Confirmation state for Save & Cancel popups ('save' | 'cancel' | null)
  const [confirmAction, setConfirmAction] = useState(null);

  const onTotalMatdarChange = (val) => {
    if (typeof handleTotalMatdarChange === 'function') {
      handleTotalMatdarChange(val);
    } else {
      if (val === '' || val === undefined || val === null) {
        setSurveyFormData((prev) => ({ ...prev, total_matdar: '' }));
        return;
      }
      const rawVal = parseInt(val, 10);
      const targetCount = isNaN(rawVal) || rawVal < 0 ? 0 : Math.min(30, rawVal);
      setSurveyFormData((prev) => {
        let updatedMembers = [...(prev.members || [])];
        if (targetCount === 0) {
          updatedMembers = [];
        } else if (targetCount > updatedMembers.length) {
          for (let i = updatedMembers.length; i < targetCount; i++) {
            updatedMembers.push({
              id: `mem_${Date.now()}_${i}`,
              full_name: '',
              dob: '',
              age: '',
              has_voter_id: 'YES',
              voter_id_no: '',
              relation: updatedMembers.length === 0 && i === 0 ? 'कुटुंब प्रमुख (Self)' : 'इतर (Other)',
            });
          }
        } else if (targetCount < updatedMembers.length) {
          updatedMembers = updatedMembers.slice(0, targetCount);
        }
        return {
          ...prev,
          total_matdar: targetCount,
          members: updatedMembers,
        };
      });
    }
  };

  const RELATION_OPTIONS = [
    { value: 'कुटुंब प्रमुख (Self)', mr: 'कुटुंब प्रमुख', en: 'Family Head (Self)' },
    { value: 'पत्नी (Wife)', mr: 'पत्नी', en: 'Wife' },
    { value: 'पती (Husband)', mr: 'पती', en: 'Husband' },
    { value: 'मुलगा (Son)', mr: 'मुलगा', en: 'Son' },
    { value: 'मुलगी (Daughter)', mr: 'मुलगी', en: 'Daughter' },
    { value: 'वडील (Father)', mr: 'वडील', en: 'Father' },
    { value: 'आई (Mother)', mr: 'आई', en: 'Mother' },
    { value: 'भाऊ (Brother)', mr: 'भाऊ', en: 'Brother' },
    { value: 'बहीण (Sister)', mr: 'बहीण', en: 'Sister' },
    { value: 'सून (Daughter-in-law)', mr: 'सून', en: 'Daughter-in-law' },
    { value: 'जावई (Son-in-law)', mr: 'जावई', en: 'Son-in-law' },
    { value: 'इतर (Other)', mr: 'इतर', en: 'Other' },
  ];

  const handleRequestSave = (e) => {
    if (e) e.preventDefault();
    if (!surveyFormData.pramukh_name || !surveyFormData.pramukh_name.trim()) {
      alert(isMarathi ? 'कृपया कुटुंब प्रमुखाचे नाव भरा.' : 'Please enter Family Head Name.');
      return;
    }
    setConfirmAction('save');
  };

  const handleConfirmSave = (e) => {
    setConfirmAction(null);
    handleSurveySubmit(e);
  };

  const handleConfirmCancel = () => {
    setConfirmAction(null);
    setActiveSurveyToFill(null);
  };

  // Confirmation Modal for Save & Cancel
  const renderConfirmationModal = () => {
    if (!confirmAction) return null;
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
        onClick={() => setConfirmAction(null)}
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
              background: confirmAction === 'save' ? '#dcfce7' : '#fee2e2',
              border: `2.5px solid ${confirmAction === 'save' ? '#86efac' : '#fca5a5'}`,
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              fontSize: '26px',
            }}
          >
            {confirmAction === 'save' ? '💾' : '⚠️'}
          </div>

          {/* Popup Title */}
          <h3 style={{ margin: 0, fontSize: '18px', fontWeight: 850, color: '#0f172a' }}>
            {confirmAction === 'save'
              ? (isMarathi ? 'खात्री करा: माहिती जतन करायची आहे का?' : 'Confirm: Do you want to save?')
              : (isMarathi ? 'खात्री करा: फॉर्म भरणे रद्द करायचे आहे का?' : 'Confirm: Do you want to cancel?')}
          </h3>

          {/* Popup Description */}
          <div style={{ fontSize: '13.5px', color: '#475569', lineHeight: 1.55 }}>
            {confirmAction === 'save' ? (
              isMarathi ? (
                <>
                  कुटुंब प्रमुख: <strong>{surveyFormData.pramukh_name}</strong>
                  {surveyFormData.phone ? <> • फोन: <strong>{surveyFormData.phone}</strong></> : null}
                  <br />
                  एकूण मतदार संख्या: <strong>{surveyFormData.total_matdar}</strong>
                  <br />
                  <span style={{ color: '#15803d', fontWeight: 700, display: 'inline-block', marginTop: '6px' }}>
                    तुम्ही भरलेली माहिती बरोबर आहे का? ही नोंद थेट डेटाबेसमध्ये जतन केली जाईल.
                  </span>
                </>
              ) : (
                <>
                  Family Head: <strong>{surveyFormData.pramukh_name}</strong>
                  {surveyFormData.phone ? <> • Phone: <strong>{surveyFormData.phone}</strong></> : null}
                  <br />
                  Total Voters: <strong>{surveyFormData.total_matdar}</strong>
                  <br />
                  <span style={{ color: '#15803d', fontWeight: 700, display: 'inline-block', marginTop: '6px' }}>
                    Are you sure all details are accurate? This entry will be saved to the database.
                  </span>
                </>
              )
            ) : (
              isMarathi ? (
                <>
                  तुम्ही फॉर्म भरणे रद्द केल्यास आतापर्यंत भरलेली माहिती साठवली जाणार नाही आणि फॉर्म बंद होईल.
                  <br />
                  <span style={{ color: '#b91c1c', fontWeight: 700, display: 'inline-block', marginTop: '6px' }}>
                    तुम्ही नक्की रद्द करू इच्छिता का?
                  </span>
                </>
              ) : (
                <>
                  Any unsaved survey details entered in this form will be discarded and the form will close.
                  <br />
                  <span style={{ color: '#b91c1c', fontWeight: 700, display: 'inline-block', marginTop: '6px' }}>
                    Are you sure you want to cancel and exit?
                  </span>
                </>
              )
            )}
          </div>

          {/* Action Buttons */}
          <div style={{ display: 'flex', gap: '12px', width: '100%', marginTop: '6px' }}>
            <button
              type="button"
              onClick={() => setConfirmAction(null)}
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
              {confirmAction === 'save'
                ? (isMarathi ? 'मागे जा / तपासा' : 'Review / Go Back')
                : (isMarathi ? 'फॉर्म सुरू ठेवा' : 'Continue Filling')}
            </button>

            <button
              type="button"
              onClick={confirmAction === 'save' ? handleConfirmSave : handleConfirmCancel}
              style={{
                flex: 1,
                padding: '11px 16px',
                borderRadius: '10px',
                border: 'none',
                background: confirmAction === 'save'
                  ? 'linear-gradient(135deg, #16a34a 0%, #15803d 100%)'
                  : 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
                color: '#ffffff',
                fontWeight: 800,
                fontSize: '13.5px',
                cursor: 'pointer',
                boxShadow: confirmAction === 'save'
                  ? '0 4px 14px rgba(22, 163, 74, 0.35)'
                  : '0 4px 14px rgba(239, 68, 68, 0.35)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                gap: '6px',
              }}
            >
              {confirmAction === 'save'
                ? (isMarathi ? '✓ होय, जतन करा' : '✓ Yes, Save')
                : (isMarathi ? '✕ होय, रद्द करा' : '✕ Yes, Cancel')}
            </button>
          </div>
        </div>
      </div>
    );
  };

  // FULL PAGE VIEW: If user clicked "फॉर्म भरा" on an assigned survey, render full-page form instead of modal popup
  if (activeSurveyToFill) {
    return (
      <div className="worker-survey-page-wrapper">
        {/* Full-Page Header with Back Button */}
        <div className="worker-survey-page-header">
          <button
            type="button"
            onClick={() => setConfirmAction('cancel')}
            className="takrari-back-btn"
            title={isMarathi ? 'मागे जा' : 'Go Back'}
          >
            <ArrowLeft size={18} />
            <span>{isMarathi ? 'मागे जा' : 'Back'}</span>
          </button>

          <div className="worker-survey-header-info">
            <div className="worker-survey-badge-icon">📋</div>
            <div style={{ flex: 1, minWidth: 0 }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '8px', flexWrap: 'wrap' }}>
                <h2 className="worker-survey-page-title">
                  {isMarathi ? (activeSurveyToFill.title_mr || activeSurveyToFill.title_en) : (activeSurveyToFill.title_en || activeSurveyToFill.title_mr)}
                </h2>
                <span className="worker-survey-version-tag">
                  v{activeSurveyToFill.version || '1.0'}
                </span>
                <span className="worker-survey-fillonly-tag">
                  ✓ {isMarathi ? 'अधिकृत फॉर्म' : 'Official Form (Fill Only)'}
                </span>
              </div>
              <div className="worker-survey-page-subtitle">
                <span>🏷️ {isMarathi ? 'प्रकल्प:' : 'Project:'} <strong>{activeSurveyToFill.project_name || (isMarathi ? 'निवडणूक मोहीम' : 'Campaign')}</strong></span>
                <span>•</span>
                <span>📍 {isMarathi ? 'व्याप्ती:' : 'Scope:'} <strong>{activeSurveyToFill.scope || (isMarathi ? 'सामान्य' : 'General')}</strong></span>
                <span>•</span>
                <span>{isMarathi ? 'कुटुंबाची व मतदारांची अचूक माहिती भरून खाली जतन करा' : 'Fill family & voter details accurately and save below'}</span>
              </div>
            </div>
          </div>
        </div>

        {/* Survey Full-Page Form Card Container */}
        <div className="worker-survey-page-card">
          <form onSubmit={handleRequestSave} style={{ display: 'flex', flexDirection: 'column', gap: '22px' }}>
            {/* SECTION 1: कुटुंब प्रमुख व घर तपशील */}
            <div className="worker-survey-section">
              <div className="worker-survey-section-head">
                <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                  <span className="worker-survey-section-number" style={{ background: '#2563eb' }}>१</span>
                  <div>
                    <h4 className="worker-survey-section-title">
                      {isMarathi ? 'कुटुंब प्रमुख व संपर्क तपशील' : 'Family Head & Contact Details'}
                    </h4>
                    <div className="worker-survey-section-subtitle">
                      {isMarathi ? 'कुटुंब प्रमुखाचे नाव, एकूण मतदार संख्या, मोबाईल व पत्ता भरा.' : 'Enter family head name, total voters, contact and address.'}
                    </div>
                  </div>
                </div>
              </div>

              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(240px, 1fr))', gap: '16px' }}>
                <div className="worker-form-field">
                  <label>
                    <span>👤</span> {isMarathi ? 'कुटुंब प्रमुखाचे पूर्ण नाव' : 'Family Head Full Name'} <span style={{ color: '#ef4444' }}>*</span>
                  </label>
                  <input
                    type="text"
                    required
                    placeholder={isMarathi ? 'उदा. सतीश विठ्ठल खोत' : 'e.g. Satish Vitthal Khot'}
                    value={surveyFormData.pramukh_name}
                    onChange={(e) => {
                      const val = e.target.value;
                      setSurveyFormData((prev) => {
                        const nextMembers = [...(prev.members || [])];
                        if (nextMembers.length > 0 && (!nextMembers[0].full_name || nextMembers[0].full_name === prev.pramukh_name)) {
                          nextMembers[0].full_name = val;
                        }
                        return { ...prev, pramukh_name: val, members: nextMembers };
                      });
                    }}
                  />
                </div>

                <div className="worker-form-field">
                  <label>
                    <span>🎯</span> {isMarathi ? 'एकूण मतदार संख्या' : 'Total Voters'} <span style={{ color: '#ef4444' }}>*</span>
                  </label>
                  <input
                    type="number"
                    min="0"
                    max="30"
                    required
                    placeholder={isMarathi ? 'उदा. ० किंवा ३' : 'e.g. 0 or 3'}
                    value={surveyFormData.total_matdar === 0 ? 0 : (surveyFormData.total_matdar ?? '')}
                    onChange={(e) => onTotalMatdarChange(e.target.value)}
                  />
                </div>

                <div className="worker-form-field">
                  <label>
                    <span>📱</span> {isMarathi ? 'मोबाईल नंबर' : 'Mobile Number'}
                  </label>
                  <input
                    type="tel"
                    maxLength="10"
                    placeholder={isMarathi ? 'उदा. ९८XXXXXXXX' : 'e.g. 98XXXXXXXX'}
                    value={surveyFormData.phone}
                    onChange={(e) => {
                      const val = e.target.value.replace(/\D/g, '');
                      setSurveyFormData({ ...surveyFormData, phone: val });
                      if (val.length >= 10) {
                        checkDuplicateField('phone', val, setSurveyDuplicateWarning);
                      } else {
                        setSurveyDuplicateWarning(null);
                      }
                    }}
                  />
                  {surveyDuplicateWarning && (
                    <div style={{ marginTop: '5px', padding: '6px 10px', background: '#fffbeb', border: '1px solid #fde68a', borderRadius: '6px', color: '#b45309', fontSize: '11px', display: 'flex', alignItems: 'center', gap: '5px' }}>
                      <AlertTriangle size={13} style={{ flexShrink: 0 }} />
                      <span>⚠️ {surveyDuplicateWarning}</span>
                    </div>
                  )}
                </div>

                <div className="worker-form-field">
                  <label>
                    <span>📍</span> {isMarathi ? 'घराचा पत्ता / परिसर' : 'House Address / Area'}
                  </label>
                  <input
                    type="text"
                    placeholder={isMarathi ? 'उदा. घर क्र. २४, मेन रोड, वॉर्ड ३' : 'e.g. House No. 24, Main Road, Ward 3'}
                    value={surveyFormData.address}
                    onChange={(e) => setSurveyFormData({ ...surveyFormData, address: e.target.value })}
                  />
                </div>
              </div>
            </div>

            {/* SECTION 2: सर्व कुटुंब सदस्यांची माहिती */}
            <div className="worker-survey-section">
              <div className="worker-survey-section-head">
                <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                  <span className="worker-survey-section-number" style={{ background: '#10b981' }}>२</span>
                  <div>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '8px', flexWrap: 'wrap' }}>
                      <h4 className="worker-survey-section-title">
                        {isMarathi ? 'सर्व कुटुंब सदस्यांची माहिती' : 'All Family Members Details'}
                      </h4>
                      <span style={{
                        background: '#dcfce7',
                        color: '#15803d',
                        fontSize: '11.5px',
                        fontWeight: 800,
                        padding: '2px 8px',
                        borderRadius: '12px',
                        border: '1px solid #bbf7d0'
                      }}>
                        {surveyFormData.members?.length || 0} {isMarathi ? 'सदस्य' : 'members'}
                      </span>
                    </div>
                    <div className="worker-survey-section-subtitle">
                      {isMarathi
                        ? `एकूण ${surveyFormData.total_matdar === 0 ? 0 : (surveyFormData.total_matdar || surveyFormData.members?.length || 0)} सदस्यांची माहिती खाली भरणे आवश्यक आहे.`
                        : `Please fill in details for all ${surveyFormData.total_matdar === 0 ? 0 : (surveyFormData.total_matdar || surveyFormData.members?.length || 0)} family members.`}
                    </div>
                  </div>
                </div>
                <button
                  type="button"
                  onClick={handleWorkerAddMember}
                  style={{
                    background: '#10b981',
                    color: '#ffffff',
                    border: 'none',
                    padding: '8px 16px',
                    borderRadius: '8px',
                    fontSize: '13px',
                    fontWeight: 750,
                    cursor: 'pointer',
                    display: 'inline-flex',
                    alignItems: 'center',
                    gap: '6px',
                    boxShadow: '0 2px 6px rgba(16, 185, 129, 0.25)'
                  }}
                >
                  <Plus size={16} />{isMarathi ? 'सदस्य जोडा' : 'Add Member'}
                </button>
              </div>

              <div style={{ display: 'flex', flexDirection: 'column', gap: '14px' }}>
                {surveyFormData.members.length === 0 ? (
                  <div style={{
                    textAlign: 'center',
                    padding: '2.5rem 1.5rem',
                    background: '#f8fafc',
                    borderRadius: '14px',
                    border: '1.5px dashed #cbd5e1',
                    color: '#64748b'
                  }}>
                    <div style={{ fontSize: '32px', marginBottom: '8px' }}>👥</div>
                    <div style={{ fontWeight: 800, fontSize: '15px', color: '#1e293b', marginBottom: '4px' }}>
                      {isMarathi ? 'या कुटुंबात ० मतदार नोंदवले आहेत' : '0 voters registered for this family'}
                    </div>
                    <div style={{ fontSize: '13px', color: '#64748b', marginBottom: '14px' }}>
                      {isMarathi
                        ? 'नवीन सदस्य जोडण्यासाठी वरील \'+ सदस्य जोडा\' बटण दाबा किंवा \'एकूण मतदार संख्या\' मध्ये बदल करा.'
                        : 'Click \'+ Add Member\' button above or change \'Total Voters\' to add members.'}
                    </div>
                    <button
                      type="button"
                      onClick={handleWorkerAddMember}
                      className="btn-primary"
                      style={{ padding: '8px 20px', fontSize: '13px', background: '#10b981', display: 'inline-flex', alignItems: 'center', gap: '6px', margin: '0 auto' }}
                    >
                      <Plus size={15} /> {isMarathi ? 'सदस्य जोडा' : 'Add Member'}
                    </button>
                  </div>
                ) : (
                  surveyFormData.members.map((member, idx) => {
                    const isHead = idx === 0;
                    return (
                      <div
                        key={member.id}
                        className={`worker-member-card ${isHead ? 'is-head' : ''}`}
                      >
                        <div className="worker-member-card-header">
                          <span className={`worker-member-badge ${isHead ? 'is-head' : ''}`}>
                            {isHead ? '👤' : '🧑'} #{idx + 1} • {isHead ? (isMarathi ? 'कुटुंब प्रमुख' : 'Family Head') : (isMarathi ? `सदस्य ${idx + 1}` : `Member ${idx + 1}`)}
                          </span>
                          <button
                            type="button"
                            onClick={() => handleWorkerRemoveMember(member.id)}
                            className="worker-member-remove-btn"
                            title={isMarathi ? 'हा सदस्य काढा' : 'Remove this member'}
                          >
                            <Trash2 size={13} /> {isMarathi ? 'काढा' : 'Remove'}
                          </button>
                        </div>

                        <div className="worker-member-fields-grid">
                          <div className="worker-form-field">
                            <label>
                              {isMarathi ? 'सदस्याचे पूर्ण नाव' : 'Member Full Name'} <span style={{ color: '#ef4444' }}>*</span>
                            </label>
                            <input
                              type="text"
                              required
                              placeholder={isMarathi ? 'उदा. अनिता सतीश खोत' : 'e.g. Anita Satish Khot'}
                              value={member.full_name}
                              onChange={(e) => handleWorkerMemberChange(member.id, 'full_name', e.target.value)}
                            />
                          </div>

                          <div className="worker-form-field">
                            <label>
                              {isMarathi ? 'नातेसंबंध' : 'Relation'}
                            </label>
                            <select
                              value={member.relation}
                              onChange={(e) => handleWorkerMemberChange(member.id, 'relation', e.target.value)}
                            >
                              {RELATION_OPTIONS.map((opt) => (
                                <option key={opt.value} value={opt.value}>
                                  {isMarathi ? opt.mr : opt.en}
                                </option>
                              ))}
                            </select>
                          </div>

                          <div className="worker-form-field">
                            <label>
                              {isMarathi ? 'जन्मतारीख' : 'Date of Birth'}
                            </label>
                            <input
                              type="date"
                              value={member.dob}
                              onChange={(e) => handleWorkerMemberChange(member.id, 'dob', e.target.value)}
                            />
                          </div>

                          <div className="worker-form-field">
                            <label>
                              {isMarathi ? 'वय' : 'Age'}
                            </label>
                            <input
                              type="number"
                              min="0"
                              max="125"
                              placeholder={isMarathi ? 'वय' : 'Age'}
                              value={member.age}
                              onChange={(e) => handleWorkerMemberChange(member.id, 'age', e.target.value)}
                            />
                          </div>

                          <div className="worker-form-field">
                            <label>
                              {isMarathi ? 'मतदार ओळखपत्र आहे का?' : 'Has Voter ID?'}
                            </label>
                            <select
                              value={member.has_voter_id}
                              onChange={(e) => handleWorkerMemberChange(member.id, 'has_voter_id', e.target.value)}
                            >
                              <option value="YES">{isMarathi ? 'होय' : 'Yes'}</option>
                              <option value="NO">{isMarathi ? 'नाही' : 'No'}</option>
                            </select>
                          </div>

                          {member.has_voter_id === 'YES' && (
                            <div className="worker-form-field">
                              <label>
                                {isMarathi ? 'मतदार ओळखपत्र क्र. (EPIC)' : 'Voter ID No. (EPIC)'}
                              </label>
                              <input
                                type="text"
                                placeholder={isMarathi ? 'उदा. MH/44/282/...' : 'e.g. MH/44/282/...'}
                                value={member.voter_id_no}
                                onChange={(e) => handleWorkerMemberChange(member.id, 'voter_id_no', e.target.value)}
                              />
                            </div>
                          )}
                        </div>
                      </div>
                    );
                  })
                )}
              </div>
            </div>

            {/* SECTION 3: अतिरिक्त सर्वेक्षण प्रश्न (कस्टम प्रश्न) */}
            {activeSurveyToFill.custom_questions && activeSurveyToFill.custom_questions.length > 0 && (
              <div className="worker-survey-section">
                <div className="worker-survey-section-head">
                  <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                    <span className="worker-survey-section-number" style={{ background: '#8b5cf6' }}>३</span>
                    <div>
                      <h4 className="worker-survey-section-title">
                        {isMarathi ? 'अतिरिक्त सर्वेक्षण प्रश्न' : 'Custom Survey Questions'}
                      </h4>
                      <div className="worker-survey-section-subtitle">
                        {isMarathi ? 'या मोहिमेसाठी विचारलेले विशिष्ट प्रश्न खाली उत्तरे निवडा.' : 'Select answers to the campaign-specific questions below.'}
                      </div>
                    </div>
                  </div>
                </div>

                <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
                  {activeSurveyToFill.custom_questions.map((q, qIdx) => (
                    <div
                      key={q.id || qIdx}
                      style={{
                        background: '#f8fafc',
                        borderRadius: '14px',
                        padding: '18px 20px',
                        border: '1.5px solid #e2e8f0',
                        boxShadow: '0 2px 6px rgba(0,0,0,0.02)'
                      }}
                    >
                      <label style={{ display: 'block', fontSize: '13.5px', fontWeight: 800, color: '#1e293b', marginBottom: '6px' }}>
                        {isMarathi ? 'प्रश्न' : 'Question'} {qIdx + 1}: {isMarathi ? (q.title_mr || q.title_en) : (q.title_en || q.title_mr)} {q.required && <span style={{ color: '#ef4444' }}>*</span>}
                      </label>

                      {/* Single Choice / Radio */}
                      {q.type === 'single_choice' && (
                        <div style={{ display: 'flex', flexWrap: 'wrap', gap: '10px', marginTop: '6px' }}>
                          {(q.options || (isMarathi ? ['होय', 'नाही'] : ['Yes', 'No'])).map((opt, oIdx) => (
                            <label
                              key={oIdx}
                              style={{
                                display: 'flex',
                                alignItems: 'center',
                                gap: '6px',
                                padding: '8px 16px',
                                borderRadius: '8px',
                                background: surveyFormData.custom_answers?.[q.id] === opt ? '#eff6ff' : '#f8fafc',
                                border: surveyFormData.custom_answers?.[q.id] === opt ? '2px solid #2563eb' : '1px solid #cbd5e1',
                                color: surveyFormData.custom_answers?.[q.id] === opt ? '#1e40af' : '#334155',
                                cursor: 'pointer',
                                fontWeight: 600,
                                fontSize: '13px'
                              }}
                            >
                              <input
                                type="radio"
                                name={`q_${q.id}`}
                                checked={surveyFormData.custom_answers?.[q.id] === opt}
                                onChange={() => handleCustomAnswerChange(q.id, opt)}
                                style={{ accentColor: '#2563eb' }}
                              />
                              {opt}
                            </label>
                          ))}
                        </div>
                      )}

                      {/* Multiple Choice / Checkbox */}
                      {q.type === 'multi_choice' && (
                        <div style={{ display: 'flex', flexWrap: 'wrap', gap: '10px', marginTop: '6px' }}>
                          {(q.options || []).map((opt, oIdx) => {
                            const isChecked = Array.isArray(surveyFormData.custom_answers?.[q.id]) && surveyFormData.custom_answers[q.id].includes(opt);
                            return (
                              <label
                                key={oIdx}
                                style={{
                                  display: 'flex',
                                  alignItems: 'center',
                                  gap: '6px',
                                  padding: '8px 16px',
                                  borderRadius: '8px',
                                  background: isChecked ? '#f0fdf4' : '#f8fafc',
                                  border: isChecked ? '2px solid #16a34a' : '1px solid #cbd5e1',
                                  color: isChecked ? '#15803d' : '#334155',
                                  cursor: 'pointer',
                                  fontWeight: 600,
                                  fontSize: '13px'
                                }}
                              >
                                <input
                                  type="checkbox"
                                  checked={isChecked}
                                  onChange={() => handleMultiChoiceToggle(q.id, opt)}
                                  style={{ accentColor: '#16a34a' }}
                                />
                                {opt}
                              </label>
                            );
                          })}
                        </div>
                      )}

                      {/* Yes / No Toggle */}
                      {q.type === 'yes_no' && (
                        <div style={{ display: 'flex', gap: '12px', marginTop: '6px' }}>
                          {[
                            { val: 'होय', mr: 'होय', en: 'Yes' },
                            { val: 'नाही', mr: 'नाही', en: 'No' },
                          ].map((opt) => (
                            <button
                              type="button"
                              key={opt.val}
                              onClick={() => handleCustomAnswerChange(q.id, opt.val)}
                              style={{
                                flex: 1,
                                padding: '9px 16px',
                                borderRadius: '8px',
                                border: (surveyFormData.custom_answers?.[q.id] === opt.val || surveyFormData.custom_answers?.[q.id] === opt.en || surveyFormData.custom_answers?.[q.id]?.startsWith(opt.mr)) ? '2px solid #0284c7' : '1px solid #cbd5e1',
                                background: (surveyFormData.custom_answers?.[q.id] === opt.val || surveyFormData.custom_answers?.[q.id] === opt.en || surveyFormData.custom_answers?.[q.id]?.startsWith(opt.mr)) ? '#e0f2fe' : '#ffffff',
                                color: (surveyFormData.custom_answers?.[q.id] === opt.val || surveyFormData.custom_answers?.[q.id] === opt.en || surveyFormData.custom_answers?.[q.id]?.startsWith(opt.mr)) ? '#0369a1' : '#334155',
                                fontWeight: 700,
                                fontSize: '13.5px',
                                cursor: 'pointer'
                              }}
                            >
                              {isMarathi ? opt.mr : opt.en}
                            </button>
                          ))}
                        </div>
                      )}

                      {/* Rating 1 to 5 */}
                      {q.type === 'rating' && (
                        <div style={{ display: 'flex', gap: '8px', marginTop: '6px' }}>
                          {[1, 2, 3, 4, 5].map((num) => (
                            <button
                              type="button"
                              key={num}
                              onClick={() => handleCustomAnswerChange(q.id, num)}
                              style={{
                                flex: 1,
                                padding: '9px',
                                borderRadius: '8px',
                                border: (surveyFormData.custom_answers?.[q.id] || 5) === num ? '2px solid #f97316' : '1px solid #cbd5e1',
                                background: (surveyFormData.custom_answers?.[q.id] || 5) === num ? '#fff7ed' : '#ffffff',
                                color: (surveyFormData.custom_answers?.[q.id] || 5) === num ? '#ea580c' : '#64748b',
                                fontWeight: 800,
                                fontSize: '14px',
                                cursor: 'pointer'
                              }}
                            >
                              ★ {num}
                            </button>
                          ))}
                        </div>
                      )}

                      {/* Text Input */}
                      {q.type === 'text' && (
                        <input
                          type="text"
                          className="form-input"
                          style={{ marginTop: '6px' }}
                          placeholder={isMarathi ? 'उत्तर येथे लिहा...' : 'Enter answer here...'}
                          value={surveyFormData.custom_answers?.[q.id] || ''}
                          onChange={(e) => handleCustomAnswerChange(q.id, e.target.value)}
                        />
                      )}

                      {/* Textarea */}
                      {q.type === 'textarea' && (
                        <textarea
                          rows="2"
                          className="form-input"
                          style={{ marginTop: '6px' }}
                          placeholder={isMarathi ? 'सविस्तर अभिप्राय येथे लिहा...' : 'Enter detailed feedback here...'}
                          value={surveyFormData.custom_answers?.[q.id] || ''}
                          onChange={(e) => handleCustomAnswerChange(q.id, e.target.value)}
                        />
                      )}
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Form Footer Action Bar */}
            <div className="worker-survey-form-actions">
              <div className="worker-survey-info-hint">
                <span>🔒</span>
                <span>{isMarathi ? 'सर्व माहिती अचूक असल्याची खात्री करून जतन करा.' : 'Ensure all details are accurate before saving.'}</span>
              </div>

              <div style={{ display: 'flex', gap: '12px', alignItems: 'center' }}>
                <button
                  type="button"
                  onClick={() => setConfirmAction('cancel')}
                  disabled={submittingSurvey}
                  className="worker-survey-btn-cancel"
                >
                  <span>✕</span>
                  <span>{isMarathi ? 'रद्द करा' : 'Cancel'}</span>
                </button>

                <button
                  type="submit"
                  disabled={submittingSurvey}
                  className="worker-survey-btn-save"
                >
                  <Save size={16} />
                  <span>
                    {submittingSurvey
                      ? (isMarathi ? 'जतन होत आहे...' : 'Saving...')
                      : (isMarathi ? '✓ फॉर्म जतन करा' : '✓ Save Form')}
                  </span>
                </button>
              </div>
            </div>
          </form>
        </div>

        {/* Save & Cancel Confirmation Popup */}
        {renderConfirmationModal()}
      </div>
    );
  }

  return (
    <div className="module-container" style={{ display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>
      {/* Page Header */}
      <div className="page-header" style={{ marginBottom: 0, display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '12px' }}>
        <div>
          <h1 className="page-title" style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
            <ClipboardCheck size={28} color="#2563eb" />
            {isMarathi ? 'मतदार कुटुंब सर्वेक्षण डेस्क' : 'Household Survey Desk'}
          </h1>
          <div className="page-subtitle">
            {isMarathi
              ? 'प्रशासनाकडून तुम्हाला केवळ अधिकृत वाटप केलेले फॉर्म. बॅकएंडमध्ये जमा झालेले व शिल्लक राहिलेले फॉर्म येथे थेट तपासू शकता.'
              : 'Survey forms strictly allocated to your account. Track forms saved to backend and pending target allocations.'}
          </div>
        </div>
        <div style={{ display: 'flex', alignItems: 'center', gap: '8px', flexWrap: 'wrap' }}>
          <span style={{
            background: '#eff6ff',
            color: '#1d4ed8',
            padding: '6px 14px',
            borderRadius: '20px',
            fontWeight: 700,
            fontSize: '13px',
            border: '1px solid #bfdbfe',
            display: 'flex',
            alignItems: 'center',
            gap: '6px'
          }}>
            <Shield size={14} />
            {isMarathi ? `वाटप केलेले फॉर्म: ${surveyStats.allocatedForms}` : `Allocated Forms: ${surveyStats.allocatedForms}`}
          </span>
          <span style={{
            background: '#f0fdf4',
            color: '#16a34a',
            padding: '6px 14px',
            borderRadius: '20px',
            fontWeight: 700,
            fontSize: '13px',
            border: '1px solid #bbf7d0',
            display: 'flex',
            alignItems: 'center',
            gap: '6px'
          }}>
            <CheckCircle2 size={14} />
            {isMarathi ? `बॅकएंडमध्ये जतन: ${surveyStats.savedToBackend}` : `Saved in Backend: ${surveyStats.savedToBackend}`}
          </span>
        </div>
      </div>

      {/* Real-time KPI Stats: Allocated to this user & Saved to backend */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(210px, 1fr))', gap: '1rem' }}>
        {/* 1. Allocated Survey Forms to this User */}
        <div style={{
          background: '#ffffff',
          border: '1px solid #bfdbfe',
          borderRadius: '14px',
          padding: '1.1rem 1.25rem',
          boxShadow: '0 2px 8px rgba(37, 99, 235, 0.06)',
          display: 'flex',
          alignItems: 'center',
          gap: '14px'
        }}>
          <div style={{
            background: 'linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%)',
            color: '#1d4ed8',
            borderRadius: '12px',
            width: '46px',
            height: '46px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            flexShrink: 0
          }}>
            <ClipboardCheck size={24} />
          </div>
          <div style={{ flex: 1, minWidth: 0 }}>
            <div style={{ fontSize: '11.5px', fontWeight: '750', color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
              {isMarathi ? 'वाटप केलेले फॉर्म' : 'Allocated Forms'}
            </div>
            <div style={{ fontSize: '26px', fontWeight: '900', color: '#1e3a8a', lineHeight: 1.2 }}>
              {surveyStats.allocatedForms}
            </div>
            <div style={{ fontSize: '11px', color: '#2563eb', fontWeight: '700', marginTop: '2px' }}>
              ✓ {isMarathi ? 'केवळ तुम्हाला अधिकृत वाटप' : 'Allocated to this user'}
            </div>
          </div>
        </div>

        {/* 2. Saved to Backend DB by this User */}
        <div style={{
          background: '#ffffff',
          border: '1px solid #bbf7d0',
          borderRadius: '14px',
          padding: '1.1rem 1.25rem',
          boxShadow: '0 2px 8px rgba(22, 163, 74, 0.06)',
          display: 'flex',
          alignItems: 'center',
          gap: '14px'
        }}>
          <div style={{
            background: 'linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%)',
            color: '#16a34a',
            borderRadius: '12px',
            width: '46px',
            height: '46px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            flexShrink: 0
          }}>
            <Save size={24} />
          </div>
          <div style={{ flex: 1, minWidth: 0 }}>
            <div style={{ fontSize: '11.5px', fontWeight: '750', color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
              {isMarathi ? 'बॅकएंडमध्ये जतन झालेले फॉर्म' : 'Saved to Backend DB'}
            </div>
            <div style={{ fontSize: '26px', fontWeight: '900', color: '#14532d', lineHeight: 1.2 }}>
              {surveyStats.savedToBackend}
            </div>
            <div style={{ fontSize: '11px', color: '#16a34a', fontWeight: '700', marginTop: '2px' }}>
              ✓ {isMarathi ? 'डेटाबेसमध्ये सुरक्षित जतन' : 'Saved in backend database'}
            </div>
          </div>
        </div>

        {/* 3. Total Target Households */}
        <div style={{
          background: '#ffffff',
          border: '1px solid #fed7aa',
          borderRadius: '14px',
          padding: '1.1rem 1.25rem',
          boxShadow: '0 2px 8px rgba(234, 88, 12, 0.06)',
          display: 'flex',
          alignItems: 'center',
          gap: '14px'
        }}>
          <div style={{
            background: 'linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%)',
            color: '#ea580c',
            borderRadius: '12px',
            width: '46px',
            height: '46px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            flexShrink: 0
          }}>
            <Target size={24} />
          </div>
          <div style={{ flex: 1, minWidth: 0 }}>
            <div style={{ fontSize: '11.5px', fontWeight: '750', color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
              {isMarathi ? 'एकूण कुटुंब उद्दिष्ट' : 'Allocated Target'}
            </div>
            <div style={{ fontSize: '26px', fontWeight: '900', color: '#7c2d12', lineHeight: 1.2 }}>
              {surveyStats.totalTarget}
            </div>
            <div style={{ fontSize: '11px', color: '#c2410c', fontWeight: '600', marginTop: '2px' }}>
              {isMarathi ? 'मतदार कुटुंब नोंदणी उद्दिष्ट' : 'Target households count'}
            </div>
          </div>
        </div>

        {/* 4. Remaining / Pending to Complete */}
        <div style={{
          background: '#ffffff',
          border: '1px solid #e2e8f0',
          borderRadius: '14px',
          padding: '1.1rem 1.25rem',
          boxShadow: '0 2px 8px rgba(100, 116, 139, 0.06)',
          display: 'flex',
          alignItems: 'center',
          gap: '14px'
        }}>
          <div style={{
            background: 'linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%)',
            color: '#64748b',
            borderRadius: '12px',
            width: '46px',
            height: '46px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            flexShrink: 0
          }}>
            <Clock size={24} />
          </div>
          <div style={{ flex: 1, minWidth: 0 }}>
            <div style={{ fontSize: '11.5px', fontWeight: '750', color: '#64748b', textTransform: 'uppercase', letterSpacing: '0.5px' }}>
              {isMarathi ? 'शिल्लक उद्दिष्ट' : 'Remaining Target'}
            </div>
            <div style={{ fontSize: '26px', fontWeight: '900', color: '#334155', lineHeight: 1.2 }}>
              {Math.max(0, surveyStats.totalTarget - surveyStats.savedToBackend)}
            </div>
            <div style={{ fontSize: '11px', color: '#64748b', fontWeight: '600', marginTop: '2px' }}>
              {isMarathi ? 'उर्वरित फॉर्म भरणे बाकी' : 'Remaining to complete'}
            </div>
          </div>
        </div>
      </div>

      {/* View Switcher: Allocated Forms vs Saved in Backend */}
      <div style={{ display: 'flex', gap: '8px', borderBottom: '1px solid #e2e8f0', paddingBottom: '4px' }}>
        <button
          type="button"
          onClick={() => setSurveyActiveView('forms')}
          style={{
            display: 'flex',
            alignItems: 'center',
            gap: '8px',
            padding: '9px 18px',
            borderRadius: '10px',
            fontWeight: 750,
            fontSize: '13.5px',
            cursor: 'pointer',
            border: 'none',
            background: surveyActiveView === 'forms' ? '#2563eb' : '#f1f5f9',
            color: surveyActiveView === 'forms' ? '#ffffff' : '#475569',
            boxShadow: surveyActiveView === 'forms' ? '0 2px 6px rgba(37,99,235,0.25)' : 'none',
            transition: 'all 0.2s ease',
          }}
        >
          <ClipboardCheck size={16} />
          <span>{isMarathi ? `वाटप केलेले अधिकृत फॉर्म (${surveysList.length})` : `Allocated Forms (${surveysList.length})`}</span>
        </button>

        <button
          type="button"
          onClick={() => setSurveyActiveView('saved_history')}
          style={{
            display: 'flex',
            alignItems: 'center',
            gap: '8px',
            padding: '9px 18px',
            borderRadius: '10px',
            fontWeight: 750,
            fontSize: '13.5px',
            cursor: 'pointer',
            border: 'none',
            background: surveyActiveView === 'saved_history' ? '#16a34a' : '#f1f5f9',
            color: surveyActiveView === 'saved_history' ? '#ffffff' : '#475569',
            boxShadow: surveyActiveView === 'saved_history' ? '0 2px 6px rgba(22,163,74,0.25)' : 'none',
            transition: 'all 0.2s ease',
          }}
        >
          <Save size={16} />
          <span>{isMarathi ? `बॅकएंडमध्ये जतन झालेले फॉर्म (${surveyStats.savedToBackend})` : `Saved in Backend (${surveyStats.savedToBackend})`}</span>
        </button>
      </div>

      {/* Access Policy Info Banner */}
      <div style={{
        background: 'linear-gradient(135deg, #f0fdf4 0%, #e0f2fe 100%)',
        border: '1px solid #bae6fd',
        borderRadius: '12px',
        padding: '12px 18px',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
        gap: '12px',
        fontSize: '13px',
        color: '#0369a1'
      }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
          <div style={{ background: '#0284c7', color: '#fff', borderRadius: '50%', width: '22px', height: '22px', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '12px', fontWeight: 800 }}>ℹ</div>
          <span>
            <strong>{isMarathi ? 'केवळ फॉर्म भरण्याची परवानगी:' : 'Fill-only Access:'}</strong>{' '}
            {isMarathi
              ? 'तुम्हाला केवळ प्रशासनाने (Admin) अधिकृत केलेल्या फॉर्ममध्ये नवीन मतदारांची माहिती भरण्याची परवानगी आहे. फॉर्मचे स्वरूप बदलता येत नाही.'
              : 'You have read and fill-only permissions for these assigned forms. Form schema cannot be altered.'}
          </span>
        </div>
      </div>

      {/* Content Views: Allocated Forms vs Saved in Backend History */}
      {surveyActiveView === 'forms' ? (
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(340px, 1fr))', gap: '1.5rem' }}>
          {activeSurveys.length > 0 ? (
            activeSurveys.map((survey) => {
              const savedCount = survey.saved_to_backend_count ?? survey.completed_voters ?? 0;
              const targetCount = survey.allocated_target || survey.total_voters || 100;
              const progressPct = Math.min(100, Math.round((savedCount / (targetCount || 1)) * 100));

              return (
                <div
                  key={survey.id}
                  className="card"
                  style={{
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '1rem',
                    padding: '1.5rem',
                    border: '1px solid #bfdbfe',
                    borderRadius: '16px',
                    boxShadow: '0 4px 12px rgba(37,99,235,0.06)',
                    background: '#ffffff',
                    position: 'relative',
                    transition: 'all 0.2s ease'
                  }}
                >
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                    <div style={{ backgroundColor: '#eff6ff', color: '#2563eb', padding: '0.65rem', borderRadius: '12px' }}>
                      <ClipboardCheck size={26} />
                    </div>
                    <div style={{ display: 'flex', gap: '6px', flexWrap: 'wrap', justifyContent: 'flex-end' }}>
                      <span style={{ backgroundColor: '#dbeafe', color: '#1d4ed8', fontSize: '0.75rem', fontWeight: 750, padding: '3px 8px', borderRadius: '6px', border: '1px solid #bfdbfe' }}>
                        ✓ {isMarathi ? 'तुम्हाला वाटप' : 'Allocated to You'}
                      </span>
                      <span style={{ backgroundColor: '#dcfce7', color: '#16a34a', fontSize: '0.75rem', fontWeight: 700, padding: '3px 8px', borderRadius: '6px' }}>
                        ✓ {isMarathi ? 'अधिकृत' : 'Authorized'}
                      </span>
                    </div>
                  </div>

                  <div>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '4px' }}>
                      <h3 style={{ fontSize: '1.2rem', fontWeight: 800, color: '#0f172a', margin: 0 }}>
                        {isMarathi ? (survey.title_mr || survey.title_en) : (survey.title_en || survey.title_mr)}
                      </h3>
                      <span style={{ fontSize: '11px', background: '#f1f5f9', color: '#475569', padding: '2px 6px', borderRadius: '4px', fontWeight: 600 }}>
                        v{survey.version || '1.0'}
                      </span>
                    </div>

                    <div style={{ display: 'flex', flexDirection: 'column', gap: '4px', marginTop: '8px', fontSize: '0.85rem', color: '#475569' }}>
                      <div>🏷️ {isMarathi ? 'प्रकल्प' : 'Project'}: <strong>{survey.project_name || (isMarathi ? 'निवडणूक मोहीम' : 'Campaign')}</strong></div>
                      <div>📍 {isMarathi ? 'भौगोलिक व्याप्ती' : 'Scope'}: <strong>{survey.scope || (isMarathi ? 'विधानसभा' : 'Assembly')}</strong></div>
                      <div style={{ color: '#059669', fontWeight: 600 }}>
                        ✓ {isMarathi ? 'कुटुंब प्रमुख + सदस्य + अतिरिक्त प्रश्न' : 'Head + Members + Custom Questions'}
                      </div>
                    </div>

                    {/* Allocated Target & Saved to Backend Block */}
                    <div style={{
                      background: '#f8fafc',
                      border: '1px solid #e2e8f0',
                      padding: '12px 14px',
                      borderRadius: '12px',
                      marginTop: '12px',
                      display: 'flex',
                      flexDirection: 'column',
                      gap: '8px'
                    }}>
                      <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.82rem' }}>
                        <span style={{ color: '#64748b', fontWeight: '600' }}>
                          🎯 {isMarathi ? 'वाटप केलेले उद्दिष्ट:' : 'Allocated Target:'}
                        </span>
                        <strong style={{ color: '#ea580c', fontWeight: '800' }}>
                          {targetCount} {isMarathi ? 'कुटुंबे' : 'Households'}
                        </strong>
                      </div>

                      <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.82rem' }}>
                        <span style={{ color: '#64748b', fontWeight: '600' }}>
                          💾 {isMarathi ? 'बॅकएंडमध्ये जतन:' : 'Saved in DB:'}
                        </span>
                        <strong style={{ color: '#16a34a', fontWeight: '850' }}>
                          {savedCount} {isMarathi ? 'फॉर्म जमा' : 'Forms Saved'}
                        </strong>
                      </div>

                      {/* Progress Bar */}
                      <div style={{ marginTop: '2px' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '11px', color: '#94a3b8', marginBottom: '3px' }}>
                          <span>{isMarathi ? 'प्रगती' : 'Progress'}</span>
                          <span style={{ fontWeight: '750', color: '#2563eb' }}>{progressPct}%</span>
                        </div>
                        <div style={{ height: '6px', background: '#e2e8f0', borderRadius: '4px', overflow: 'hidden' }}>
                          <div style={{
                            width: `${progressPct}%`,
                            height: '100%',
                            background: 'linear-gradient(90deg, #3b82f6 0%, #16a34a 100%)',
                            borderRadius: '4px',
                            transition: 'width 0.4s ease'
                          }} />
                        </div>
                      </div>
                    </div>
                  </div>

                  <div style={{ marginTop: 'auto', paddingTop: '1rem', borderTop: '1px solid #f1f5f9', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <span style={{ fontSize: '0.78rem', color: '#94a3b8', display: 'flex', alignItems: 'center', gap: '4px' }}>
                      <Lock size={12} /> {isMarathi ? 'पाहणे व भरणे अधिकृत' : 'Fill-only Access'}
                    </span>
                    <button
                      className="btn-primary"
                      style={{
                        padding: '0.55rem 1.35rem',
                        fontSize: '0.9rem',
                        fontWeight: 700,
                        display: 'flex',
                        alignItems: 'center',
                        gap: '6px',
                        background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)'
                      }}
                      onClick={() => handleOpenSurveyModal(survey)}
                    >
                      {isMarathi ? 'फॉर्म भरा →' : 'Fill Form →'}
                    </button>
                  </div>
                </div>
              );
            })
          ) : (
            <div style={{ gridColumn: '1 / -1', textAlign: 'center', padding: '4rem 2rem', background: '#ffffff', borderRadius: '16px', border: '1px dashed #cbd5e1' }}>
              <div style={{ background: '#f1f5f9', width: '64px', height: '64px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 1.2rem auto' }}>
                <ClipboardList size={32} color="#94a3b8" />
              </div>
              <h3 style={{ fontSize: '18px', fontWeight: 800, color: '#1e293b', marginBottom: '8px' }}>
                {isMarathi ? 'सध्या तुम्हाला कोणतेही सर्व्हे फॉर्म वाटप केलेले नाहीत' : 'No Survey Forms Allocated to Your Account'}
              </h3>
              <p style={{ color: '#64748b', fontSize: '14px', maxWidth: '480px', margin: '0 auto', lineHeight: 1.6 }}>
                {isMarathi
                  ? 'केवळ प्रशासनाने (Admin) तुमच्या खात्याला अधिकृत वाटप केलेले फॉर्मच येथे दिसतात. नवीन फॉर्म वाटपासाठी कृपया तुमच्या ॲडमिनशी संपर्क साधा.'
                  : 'Only published survey forms assigned to your specific user account by an Administrator appear here. Contact your campaign administrator for form allocation.'}
              </p>
            </div>
          )}
        </div>
      ) : (
        /* View 2: Saved Forms in Backend DB History */
        <div style={{
          background: '#ffffff',
          borderRadius: '16px',
          border: '1px solid #e2e8f0',
          overflow: 'hidden',
          boxShadow: '0 4px 6px -1px rgba(0,0,0,0.05)'
        }}>
          <div style={{
            padding: '16px 20px',
            background: 'linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%)',
            borderBottom: '1px solid #e2e8f0',
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            flexWrap: 'wrap',
            gap: '10px'
          }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
              <Save size={20} color="#16a34a" />
              <h3 style={{ margin: 0, fontSize: '16px', fontWeight: 800, color: '#0f172a' }}>
                {isMarathi ? 'बॅकएंड डेटाबेसमध्ये जतन झालेले फॉर्म' : 'Forms Saved in Backend Database'}
              </h3>
              <span style={{ background: '#dcfce7', color: '#16a34a', padding: '2px 8px', borderRadius: '12px', fontSize: '12px', fontWeight: 700 }}>
                {savedSubmissionsList.length} {isMarathi ? 'नोंदी' : 'Records'}
              </span>
            </div>
            <div style={{ fontSize: '12px', color: '#64748b' }}>
              🔒 {isMarathi ? 'केवळ या युझरने भरलेले फॉर्म' : 'Only forms submitted by your account'}
            </div>
          </div>

          {savedSubmissionsList.length > 0 ? (
            <div style={{ overflowX: 'auto' }}>
              <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left', fontSize: '13.5px' }}>
                <thead>
                  <tr style={{ background: '#f8fafc', borderBottom: '2px solid #e2e8f0', color: '#475569', fontWeight: '750' }}>
                    <th style={{ padding: '12px 16px' }}>#</th>
                    <th style={{ padding: '12px 16px' }}>{isMarathi ? 'कुटुंब प्रमुख' : 'Family Head'}</th>
                    <th style={{ padding: '12px 16px' }}>{isMarathi ? 'मोबाईल / ओळखपत्र' : 'Contact / Voter ID'}</th>
                    <th style={{ padding: '12px 16px' }}>{isMarathi ? 'एकूण मतदार' : 'Total Voters'}</th>
                    <th style={{ padding: '12px 16px' }}>{isMarathi ? 'पत्ता' : 'Address'}</th>
                    <th style={{ padding: '12px 16px' }}>{isMarathi ? 'जतन दिनांक' : 'Saved Date'}</th>
                    <th style={{ padding: '12px 16px' }}>{isMarathi ? 'स्थिती' : 'Status'}</th>
                  </tr>
                </thead>
                <tbody>
                  {savedSubmissionsList.map((item, idx) => (
                    <tr key={item.id || idx} style={{ borderBottom: '1px solid #f1f5f9', transition: 'background 0.15s ease' }}>
                      <td style={{ padding: '12px 16px', fontWeight: '700', color: '#64748b' }}>
                        {idx + 1}
                      </td>
                      <td style={{ padding: '12px 16px', fontWeight: '800', color: '#0f172a' }}>
                        {item.citizen_name || '-'}
                      </td>
                      <td style={{ padding: '12px 16px' }}>
                        <div style={{ fontWeight: '600', color: '#0284c7' }}>{item.phone || '-'}</div>
                        {item.voter_id && (
                          <div style={{ fontSize: '11px', color: '#64748b', fontFamily: 'monospace' }}>
                            🪪 {item.voter_id}
                          </div>
                        )}
                      </td>
                      <td style={{ padding: '12px 16px' }}>
                        <span style={{ background: '#eff6ff', color: '#1d4ed8', padding: '3px 8px', borderRadius: '6px', fontWeight: '750', fontSize: '12px' }}>
                          👥 {item.total_matdar || 1} {isMarathi ? 'सदस्य' : 'members'}
                        </span>
                      </td>
                      <td style={{ padding: '12px 16px', color: '#64748b', maxWidth: '200px', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                        {item.address || (isMarathi ? 'कार्यक्षेत्र' : 'Field Area')}
                      </td>
                      <td style={{ padding: '12px 16px', color: '#64748b', fontSize: '12px' }}>
                        {item.submitted_at ? new Date(item.submitted_at).toLocaleString(isMarathi ? 'mr-IN' : 'en-US', { dateStyle: 'medium', timeStyle: 'short' }) : '-'}
                      </td>
                      <td style={{ padding: '12px 16px' }}>
                        <span style={{
                          background: '#dcfce7',
                          color: '#15803d',
                          padding: '4px 10px',
                          borderRadius: '20px',
                          fontSize: '11.5px',
                          fontWeight: '750',
                          border: '1px solid #bbf7d0',
                          display: 'inline-flex',
                          alignItems: 'center',
                          gap: '4px'
                        }}>
                          <CheckCircle2 size={12} />
                          {isMarathi ? 'बॅकएंडमध्ये जतन' : 'Saved in DB'}
                        </span>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <div style={{ textAlign: 'center', padding: '4rem 2rem' }}>
              <div style={{ background: '#f8fafc', width: '56px', height: '56px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', margin: '0 auto 1rem auto' }}>
                <Save size={28} color="#94a3b8" />
              </div>
              <h4 style={{ margin: '0 0 6px 0', fontSize: '16px', fontWeight: 800, color: '#334155' }}>
                {isMarathi ? 'अद्याप कोणताही फॉर्म बॅकएंडमध्ये जमा केलेला नाही' : 'No forms saved to backend yet'}
              </h4>
              <p style={{ margin: '0 0 1rem 0', color: '#64748b', fontSize: '13px' }}>
                {isMarathi ? 'वरील \'वाटप केलेले अधिकृत फॉर्म\' टॅबमधून नवीन मतदारांची माहिती भरून जतन करा.' : 'Fill and submit an allocated survey form to view your saved records here.'}
              </p>
              <button
                type="button"
                onClick={() => setSurveyActiveView('forms')}
                className="btn-primary"
                style={{ padding: '8px 18px', fontSize: '13px', fontWeight: 700 }}
              >
                {isMarathi ? '← वाटप केलेले फॉर्म पहा' : '← View Allocated Forms'}
              </button>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
