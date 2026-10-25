import React, { useState, useEffect, useRef } from 'react';
import { useAuth } from '../../hooks/useAuth';
import * as authService from '../../services/authService';
import {
  Lock,
  Phone,
  Mail,
  User,
  Eye,
  EyeOff,
  ArrowRight,
  ShieldCheck,
  AlertCircle,
  CheckCircle2,
  Clock,
  Briefcase,
  Globe,
  ChevronDown,
  UserPlus,
  X,
  Loader2,
  KeyRound,
  Check
} from 'lucide-react';
import './WorkerLogin.css';

export default function WorkerLogin({ onLoginSuccess, initialMode = 'login' }) {
  const { login, register, setSession } = useAuth();

  // Language toggle: 'mr' (default) or 'en'
  const [lang, setLang] = useState(() => localStorage.getItem('officer_portal_lang') || 'mr');
  const isMr = lang === 'mr';
  const [showLangMenu, setShowLangMenu] = useState(false);

  // Active view: 'login' | 'register'
  const [mode, setMode] = useState(initialMode);

  // ===================== LOGIN FORM STATE (EMAIL/PHONE + PASSWORD ONLY) =====================
  const [identifier, setIdentifier] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [rememberMe, setRememberMe] = useState(true);

  // ===================== REGISTRATION STATE (NUMBER VERIFICATION WITH DOVE SMS) =====================
  const [regFullName, setRegFullName] = useState('');
  const [regPhone, setRegPhone] = useState('');
  const [isPhoneVerified, setIsPhoneVerified] = useState(false);
  const [regEmail, setRegEmail] = useState('');
  const [regPassword, setRegPassword] = useState('');
  const [regConfirmPassword, setRegConfirmPassword] = useState('');
  const [showRegPassword, setShowRegPassword] = useState(false);
  const [showRegConfirmPassword, setShowRegConfirmPassword] = useState(false);

  // ===================== OTP VERIFICATION POPUP MODAL (FOR REGISTRATION ONLY) =====================
  const [isOtpModalOpen, setIsOtpModalOpen] = useState(false);
  const [otpDigits, setOtpDigits] = useState(['', '', '', '', '', '']);
  const otpRefs = useRef([]);
  const [otpTimer, setOtpTimer] = useState(120);
  const [canResendOtp, setCanResendOtp] = useState(false);
  const [isSendingOtp, setIsSendingOtp] = useState(false);
  const [isVerifyingOtp, setIsVerifyingOtp] = useState(false);

  // Feedback states
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [successMsg, setSuccessMsg] = useState('');

  // Handle language switch
  const switchLanguage = (newLang) => {
    setLang(newLang);
    localStorage.setItem('officer_portal_lang', newLang);
    setShowLangMenu(false);
  };

  // Switch active mode
  const switchMode = (newMode) => {
    setMode(newMode);
    setError('');
    setSuccessMsg('');
    if (newMode === 'register') {
      setRegFullName('');
      setRegPhone('');
      setRegEmail('');
      setRegPassword('');
      setRegConfirmPassword('');
      setIsPhoneVerified(false);
    }
  };

  // Countdown timer for OTP popup
  useEffect(() => {
    let interval = null;
    if (isOtpModalOpen && otpTimer > 0) {
      interval = setInterval(() => {
        setOtpTimer((prev) => {
          if (prev <= 1) {
            setCanResendOtp(true);
            return 0;
          }
          return prev - 1;
        });
      }, 1000);
    }
    return () => clearInterval(interval);
  }, [isOtpModalOpen, otpTimer]);

  // Handle OTP digit changes
  const handleOtpChange = (index, value) => {
    const val = value.replace(/\D/g, '');
    const newDigits = [...otpDigits];

    if (!val) {
      newDigits[index] = '';
      setOtpDigits(newDigits);
      return;
    }

    newDigits[index] = val.slice(-1);
    setOtpDigits(newDigits);

    if (index < 5 && val) {
      otpRefs.current[index + 1]?.focus();
    }
  };

  const handleOtpKeyDown = (index, e) => {
    if (e.key === 'Backspace' && !otpDigits[index] && index > 0) {
      otpRefs.current[index - 1]?.focus();
    }
  };

  const handleOtpPaste = (e) => {
    e.preventDefault();
    const pasted = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
    if (pasted) {
      const newDigits = [...otpDigits];
      for (let i = 0; i < pasted.length; i++) {
        newDigits[i] = pasted[i];
      }
      setOtpDigits(newDigits);
      const nextFocus = Math.min(pasted.length, 5);
      otpRefs.current[nextFocus]?.focus();
    }
  };

  // ===================== SEND OTP FOR REGISTRATION VIA DOVE SMS GATEWAY =====================
  const handleSendRegistrationOtp = async (phoneToVerify = null) => {
    const raw = phoneToVerify || regPhone;
    const clean = raw.replace(/\D/g, '').slice(-10);

    if (clean.length !== 10) {
      setError(isMr ? 'कृपया योग्य १०-अंकी मोबाईल नंबर प्रविष्ट करा.' : 'Please enter a valid 10-digit mobile number.');
      return;
    }

    setError('');
    setSuccessMsg('');
    setIsSendingOtp(true);

    try {
      // Calls backend Dove SMS Gateway (user=Experts, key=ba9dcdcdfcXX, senderid=EXTSKL)
      const res = await authService.sendOtp(clean);

      setSuccessMsg(
        res?.message ||
        (isMr
          ? `+91 ${clean} वर पडताळणी SMS OTP पाठवला आहे!`
          : `Verification SMS OTP sent to +91 ${clean}!`)
      );

      setOtpDigits(['', '', '', '', '', '']);
      setOtpTimer(120);
      setCanResendOtp(false);
      setIsOtpModalOpen(true);
    } catch (err) {
      setError(
        err?.message ||
        (isMr ? 'SMS पाठवताना त्रुटी आली. कृपया पुन्हा प्रयत्न करा.' : 'Failed to send SMS. Please try again.')
      );
    } finally {
      setIsSendingOtp(false);
    }
  };

  // ===================== VERIFY OTP IN POPUP MODAL =====================
  const handleVerifyRegistrationOtp = async (e) => {
    e?.preventDefault();
    const enteredCode = otpDigits.join('');
    if (enteredCode.length < 6) {
      setError(isMr ? 'कृपया संपूर्ण ६-अंकी OTP प्रविष्ट करा.' : 'Please enter the complete 6-digit OTP.');
      return;
    }

    const clean = regPhone.replace(/\D/g, '').slice(-10);
    setError('');
    setIsVerifyingOtp(true);

    try {
      const res = await authService.verifyOtp(clean, enteredCode);

      if (res?.verified) {
        // If user already had an account with this number, do NOT bypass password! Redirect to login
        if (res?.is_existing_user) {
          setIsOtpModalOpen(false);
          setMode('login');
          setIdentifier(clean);
          setPassword('');
          setError(
            isMr
              ? 'हा मोबाईल नंबर आधीच नोंदणीकृत आहे! कृपया आपला पासवर्ड टाकून लॉगिन करा.'
              : 'This mobile number is already registered! Please sign in with your password.'
          );
          return;
        }

        // Successfully verified new phone number!
        setIsPhoneVerified(true);
        setIsOtpModalOpen(false);
        setSuccessMsg(
          isMr
            ? `✓ मोबाईल नंबर यशस्वीरीत्या पडताळला गेला आहे! आता कृपया ईमेल आयडी आणि पासवर्ड टाकून नोंदणी पूर्ण करा.`
            : `✓ Mobile number verified! Please enter your email ID and password to complete registration.`
        );
      } else {
        setError(res?.message || (isMr ? 'अवैध OTP कोड. कृपया पुन्हा प्रयत्न करा.' : 'Invalid OTP.'));
      }
    } catch (err) {
      setError(err?.message || (isMr ? 'अवैध OTP कोड. कृपया पुन्हा प्रयत्न करा.' : 'Invalid OTP. Please try again.'));
    } finally {
      setIsVerifyingOtp(false);
    }
  };

  // ===================== FINALIZE REGISTRATION IN DB =====================
  const finalizeRegistration = async (verifiedPhone) => {
    setLoading(true);
    setError('');

    try {
      const payload = {
        full_name: regFullName.trim(),
        phone: verifiedPhone,
        email: regEmail.trim().toLowerCase(),
        password: regPassword,
        password_confirmation: regConfirmPassword,
        role: 'OFFICER',
      };

      const res = await register(payload);

      setSuccessMsg(isMr ? 'खाते यशस्वीरीत्या तयार झाले! पोर्टलमध्ये स्वागत आहे.' : 'Account created successfully! Welcome.');

      if (onLoginSuccess) {
        setTimeout(() => onLoginSuccess(), 600);
      }
    } catch (err) {
      if (err?.errors) {
        const firstErrKey = Object.keys(err.errors)[0];
        setError(err.errors[firstErrKey]?.[0] || err.message);
      } else {
        setError(err?.message || (isMr ? 'नोंदणी अयशस्वी. कृपया पुन्हा प्रयत्न करा.' : 'Registration failed.'));
      }
    } finally {
      setLoading(false);
    }
  };

  // ===================== REGISTRATION FORM SUBMIT =====================
  const handleRegisterFormSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setSuccessMsg('');

    if (!regFullName.trim()) {
      setError(isMr ? 'कृपया आपले पूर्ण नाव प्रविष्ट करा.' : 'Please enter full name.');
      return;
    }
    const cleanPhone = regPhone.replace(/\D/g, '').slice(-10);
    if (cleanPhone.length !== 10) {
      setError(isMr ? 'कृपया १०-अंकी मोबाईल नंबर प्रविष्ट करा.' : 'Please enter a valid 10-digit mobile number.');
      return;
    }
    if (!isPhoneVerified) {
      setError(isMr ? 'कृपया आधी मोबाईल नंबर OTP द्वारे पडताळून घ्या.' : 'Please verify mobile number with OTP first.');
      handleSendRegistrationOtp(cleanPhone);
      return;
    }
    const emailVal = regEmail.trim();
    if (!emailVal || !emailVal.includes('@') || !emailVal.includes('.')) {
      setError(isMr ? 'कृपया वैध ईमेल आयडी प्रविष्ट करा.' : 'Please enter a valid email address.');
      return;
    }
    if (!regPassword || regPassword.length < 6) {
      setError(isMr ? 'पासवर्ड किमान ६ अक्षरांचा असावा.' : 'Password must be at least 6 characters.');
      return;
    }
    if (regPassword !== regConfirmPassword) {
      setError(isMr ? 'पासवर्ड आणि कन्फर्म पासवर्ड जुळत नाहीत.' : 'Passwords do not match.');
      return;
    }

    // Phone is verified AND valid email & password entered -> finalize registration in DB
    finalizeRegistration(cleanPhone);
  };

  // ===================== LOGIN FORM SUBMIT (EMAIL/PHONE + PASSWORD) =====================
  const handleLoginSubmit = async (e) => {
    e.preventDefault();
    const cleanLogin = identifier.trim().replace('#', '@');
    if (!cleanLogin || !password) {
      setError(isMr ? 'कृपया ईमेल किंवा मोबाईल नंबर आणि पासवर्ड प्रविष्ट करा.' : 'Please enter email/phone and password.');
      return;
    }

    setError('');
    setSuccessMsg('');
    setLoading(true);

    try {
      await login({ login: cleanLogin, password });
      if (onLoginSuccess) onLoginSuccess();
    } catch (err) {
      setError(
        err?.message ||
        (isMr ? 'लॉगिन अयशस्वी. कृपया योग्य क्रेडेन्शियल्स प्रविष्ट करा.' : 'Login failed. Please enter valid credentials.')
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="officer-auth-container">
      <div className="officer-phone-frame">

        {/* ================= APP HEADER ================= */}
        <header className="officer-app-header">
          <div className="officer-brand">
            <div className="officer-logo-badge">
              <Briefcase size={22} strokeWidth={2.4} />
            </div>
            <div className="officer-brand-text">
              <span className="officer-brand-title">BrainPower</span>
              <span className="officer-brand-subtitle">{isMr ? 'पदाधिकारी व कार्यकर्ता पोर्टल' : 'Officer Management Portal'}</span>
            </div>
          </div>

          {/* Language Selector */}
          <div style={{ position: 'relative' }}>
            <button
              type="button"
              className="officer-lang-btn"
              onClick={() => setShowLangMenu(!showLangMenu)}
            >
              <Globe size={14} />
              <span>{isMr ? 'मराठी' : 'English'}</span>
              <ChevronDown size={13} />
            </button>

            {showLangMenu && (
              <div style={{
                position: 'absolute',
                top: '100%',
                right: 0,
                marginTop: '6px',
                background: '#ffffff',
                borderRadius: '10px',
                boxShadow: '0 10px 25px rgba(0,0,0,0.2)',
                border: '1px solid #e2e8f0',
                overflow: 'hidden',
                zIndex: 50,
                minWidth: '110px'
              }}>
                <button
                  type="button"
                  onClick={() => switchLanguage('mr')}
                  style={{
                    display: 'block',
                    width: '100%',
                    padding: '8px 14px',
                    textAlign: 'left',
                    background: isMr ? '#eff6ff' : 'transparent',
                    border: 'none',
                    color: isMr ? '#1d4ed8' : '#334155',
                    fontSize: '13px',
                    fontWeight: isMr ? '700' : '500',
                    cursor: 'pointer'
                  }}
                >
                  मराठी
                </button>
                <button
                  type="button"
                  onClick={() => switchLanguage('en')}
                  style={{
                    display: 'block',
                    width: '100%',
                    padding: '8px 14px',
                    textAlign: 'left',
                    background: !isMr ? '#eff6ff' : 'transparent',
                    border: 'none',
                    color: !isMr ? '#1d4ed8' : '#334155',
                    fontSize: '13px',
                    fontWeight: !isMr ? '700' : '500',
                    cursor: 'pointer'
                  }}
                >
                  English
                </button>
              </div>
            )}
          </div>
        </header>

        {/* ================= HERO SECTION ================= */}
        {mode === 'login' && (
          <section className="officer-hero-section">
            <div className="officer-hero-text">
              <h1 className="officer-hero-title">
                Welcome <span className="highlight-orange">Back!</span>
              </h1>
              <p className="officer-hero-subtitle">
                {isMr ? (
                  <>
                    आपल्या अधिकृत खात्यामध्ये<br />
                    ईमेल / फोन व पासवर्डने लॉगिन करा
                  </>
                ) : (
                  <>
                    Sign in with your email/phone<br />
                    and password securely
                  </>
                )}
              </p>
            </div>
            <div className="officer-hero-graphic">
              <div className="officer-avatar-ring">
                <User size={46} className="officer-avatar-silhouette" />
                <div className="officer-badge-lock">
                  <Lock size={15} strokeWidth={2.6} />
                </div>
              </div>
            </div>
          </section>
        )}

        {mode === 'register' && (
          <section className="officer-hero-section officer-hero-section-alt">
            <div className="officer-hero-graphic-register-left">
              <UserPlus size={28} strokeWidth={2.2} />
            </div>
            <div className="officer-hero-text">
              <h1 className="officer-hero-title">
                {isMr ? 'नवीन खाते ' : 'Create '}
                <span className="highlight-orange">{isMr ? 'तयार करा' : 'Account'}</span>
              </h1>
              <p className="officer-hero-subtitle">
                {isMr
                  ? 'मोबाईल नंबर पडताळणीसह नवीन खाते सुरू करा.'
                  : 'Register with SMS number verification.'}
              </p>
            </div>
          </section>
        )}

        {/* ================= WHITE CARD CONTAINER ================= */}
        <div className="officer-card-container">

          {/* Notification Alerts */}
          {error && (
            <div className="officer-alert-banner officer-alert-error">
              <AlertCircle size={18} style={{ flexShrink: 0 }} />
              <span>{error}</span>
            </div>
          )}

          {successMsg && (
            <div className="officer-alert-banner officer-alert-success">
              <CheckCircle2 size={18} style={{ flexShrink: 0 }} />
              <span>{successMsg}</span>
            </div>
          )}

          {/* ================= VIEW 1: LOGIN (EMAIL/PHONE + PASSWORD ONLY) ================= */}
          {mode === 'login' && (
            <form onSubmit={handleLoginSubmit} className="officer-login-form" autoComplete="off">
              <div className="officer-card-header">
                <div className="officer-card-header-badge">
                  <ShieldCheck size={14} />
                  <span>{isMr ? 'सुरक्षित अधिकारी लॉगिन' : 'Secure Officer Login'}</span>
                </div>
                <p className="officer-card-subtitle">
                  {isMr
                    ? 'आपला ईमेल किंवा मोबाईल नंबर आणि पासवर्ड प्रविष्ट करा.'
                    : 'Enter your email or phone number and password to login.'}
                </p>
              </div>

              <div className="officer-login-fields">
                {/* Field 1: Email or Phone */}
                <div className="officer-form-group">
                  <label className="officer-form-label">
                    {isMr ? 'ईमेल किंवा मोबाईल नंबर' : 'Email or Phone Number'}
                    <span className="required-star">*</span>
                  </label>
                  <div className="officer-input-wrapper">
                    <span className="officer-input-icon">
                      <Mail size={18} />
                    </span>
                    <input
                      type="text"
                      name="officer_login_id"
                      autoComplete="off"
                      className="officer-input"
                      placeholder={isMr ? 'ईमेल किंवा मोबाईल नंबर टाका' : 'Enter email or mobile number'}
                      value={identifier}
                      onChange={(e) => setIdentifier(e.target.value)}
                      required
                    />
                  </div>
                </div>

                {/* Field 2: Password */}
                <div className="officer-form-group">
                  <label className="officer-form-label">
                    {isMr ? 'पासवर्ड' : 'Password'}
                    <span className="required-star">*</span>
                  </label>
                  <div className="officer-input-wrapper">
                    <span className="officer-input-icon">
                      <Lock size={18} />
                    </span>
                    <input
                      type={showPassword ? 'text' : 'password'}
                      name="officer_login_secret"
                      autoComplete="new-password"
                      className="officer-input"
                      placeholder={isMr ? 'पासवर्ड टाका' : 'Enter password'}
                      value={password}
                      onChange={(e) => setPassword(e.target.value)}
                      required
                    />
                    <button
                      type="button"
                      className="officer-input-eye-btn"
                      onClick={() => setShowPassword(!showPassword)}
                    >
                      {showPassword ? <EyeOff size={18} /> : <Eye size={18} />}
                    </button>
                  </div>
                </div>

                {/* Remember Me & Forgot Password */}
                <div className="officer-pass-row">
                  <label className="officer-remember-label">
                    <input
                      type="checkbox"
                      checked={rememberMe}
                      onChange={(e) => setRememberMe(e.target.checked)}
                    />
                    <span>{isMr ? 'मला लक्षात ठेवा' : 'Remember me'}</span>
                  </label>
                  <a
                    href="#forgot"
                    className="officer-forgot-link"
                    onClick={(e) => {
                      e.preventDefault();
                      alert(isMr ? 'पासवर्ड रीसेटसाठी कृपया आपल्या अ‍ॅडमिनशी संपर्क साधा.' : 'Please contact admin for password reset.');
                    }}
                  >
                    {isMr ? 'पासवर्ड विसरलात?' : 'Forgot password?'}
                  </a>
                </div>
              </div>

              <div className="officer-login-actions">
                <button
                  type="submit"
                  className="officer-submit-btn"
                  disabled={loading}
                >
                  <span>
                    {loading
                      ? (isMr ? 'लॉगिन होत आहे...' : 'Signing in...')
                      : (isMr ? 'लॉगिन करा' : 'Sign In')}
                  </span>
                  <ArrowRight size={18} />
                </button>
              </div>

              {/* Bottom Switch Link to Register */}
              <div className="officer-switch-footer">
                <span>{isMr ? 'खाते नाही?' : "Don't have an account?"} </span>
                <button
                  type="button"
                  className="officer-switch-link"
                  onClick={() => switchMode('register')}
                >
                  {isMr ? 'नवीन खाते तयार करा' : 'Create new account'}
                </button>
              </div>
            </form>
          )}

          {/* ================= VIEW 2: REGISTER (WITH SMS NUMBER VERIFICATION POPUP) ================= */}
          {mode === 'register' && (
            <form onSubmit={handleRegisterFormSubmit} className="officer-register-form" autoComplete="off">
              {/* Decoy inputs to absorb browser password-manager autofill (stops dkte@gmail.com auto-filling) */}
              <div style={{ position: 'absolute', opacity: 0, height: 0, width: 0, overflow: 'hidden', pointerEvents: 'none' }} tabIndex={-1} aria-hidden="true">
                <input type="text" name="dummy_username_autofill_blocker" tabIndex={-1} autoComplete="off" defaultValue="" />
                <input type="password" name="dummy_password_autofill_blocker" tabIndex={-1} autoComplete="off" defaultValue="" />
              </div>

              <div className="officer-card-header" style={{ marginBottom: '14px' }}>
                <div className="officer-card-header-badge">
                  <ShieldCheck size={14} />
                  <span>{isMr ? 'SMS पडताळणीसह नोंदणी' : 'Verified Registration'}</span>
                </div>
                <p className="officer-card-subtitle">
                  {isMr
                    ? 'आपला मोबाईल नंबर OTP द्वारे पडताळून खाते सुरू करा.'
                    : 'Verify your phone via SMS OTP to create your account.'}
                </p>
              </div>

              {/* Full Name */}
              <div className="officer-form-group">
                <label className="officer-form-label">
                  {isMr ? 'पूर्ण नाव' : 'Full Name'}
                  <span className="required-star">*</span>
                </label>
                <div className="officer-input-wrapper">
                  <span className="officer-input-icon">
                    <User size={18} />
                  </span>
                  <input
                    type="text"
                    name="reg_officer_full_name"
                    autoComplete="off"
                    className="officer-input"
                    placeholder={isMr ? 'उदा. सचिन सावंत' : 'e.g. Sachin Sawant'}
                    value={regFullName}
                    onChange={(e) => setRegFullName(e.target.value)}
                    required
                  />
                </div>
              </div>

              {/* Mobile Number Field with Clean OTP Send Button */}
              <div className="officer-form-group">
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                  <label className="officer-form-label">
                    {isMr ? 'मोबाईल नंबर' : 'Mobile Number'}
                    <span className="required-star">*</span>
                  </label>
                  {isPhoneVerified && (
                    <span className="officer-phone-verified-tag">
                      <Check size={13} /> {isMr ? 'पडताळणी पूर्ण' : 'Verified'}
                    </span>
                  )}
                </div>

                <div className="officer-phone-action-row">
                  <div className="officer-input-wrapper officer-phone-input-col">
                    <span className="officer-input-icon">
                      <Phone size={18} />
                    </span>
                    <input
                      type="tel"
                      name="reg_officer_phone_num"
                      autoComplete="off"
                      maxLength={10}
                      className="officer-input"
                      placeholder={isMr ? '१० अंकी मोबाईल नंबर प्रविष्ट करा' : 'Enter 10-digit mobile number'}
                      value={regPhone}
                      onChange={(e) => {
                        setRegPhone(e.target.value.replace(/\D/g, ''));
                        setIsPhoneVerified(false);
                      }}
                      required
                    />
                  </div>

                  {!isPhoneVerified ? (
                    <button
                      type="button"
                      className="officer-otp-trigger-btn"
                      onClick={() => handleSendRegistrationOtp()}
                      disabled={isSendingOtp || regPhone.replace(/\D/g, '').length !== 10}
                      title={regPhone.replace(/\D/g, '').length !== 10 ? (isMr ? 'आधी १० अंकी नंबर टाका' : 'Enter 10 digits first') : ''}
                    >
                      {isSendingOtp ? (
                        <>
                          <Loader2 size={14} className="admin-spin" />
                          <span>{isMr ? 'पाठवत आहे...' : 'Sending...'}</span>
                        </>
                      ) : (
                        <>
                          <span>{isMr ? 'ओटीपी पाठवा' : 'Send OTP'}</span>
                          <ArrowRight size={14} />
                        </>
                      )}
                    </button>
                  ) : (
                    <div className="officer-verified-done-box">
                      <Check size={15} />
                      <span>{isMr ? 'पडताळले' : 'Verified'}</span>
                    </div>
                  )}
                </div>
              </div>

              {/* Email ID (Mandatory) */}
              <div className="officer-form-group">
                <label className="officer-form-label">
                  {isMr ? 'ईमेल आयडी' : 'Email ID'}
                  <span className="required-star">*</span>
                </label>
                <div className="officer-input-wrapper">
                  <span className="officer-input-icon">
                    <Mail size={18} />
                  </span>
                  <input
                    type="email"
                    name="reg_officer_contact_email"
                    autoComplete="new-password"
                    autoCorrect="off"
                    spellCheck={false}
                    className="officer-input"
                    placeholder={isMr ? 'उदा. yourname@gmail.com' : 'e.g. yourname@gmail.com'}
                    value={regEmail}
                    onChange={(e) => setRegEmail(e.target.value)}
                    required
                  />
                </div>
              </div>

              {/* Password */}
              <div className="officer-form-group">
                <label className="officer-form-label">
                  {isMr ? 'पासवर्ड' : 'Password'}
                  <span className="required-star">*</span>
                </label>
                <div className="officer-input-wrapper">
                  <span className="officer-input-icon">
                    <Lock size={18} />
                  </span>
                  <input
                    type={showRegPassword ? 'text' : 'password'}
                    name="reg_officer_new_password"
                    autoComplete="new-password"
                    className="officer-input"
                    placeholder={isMr ? 'किमान ६ अक्षरांचा पासवर्ड तयार करा' : 'Min 6 characters'}
                    value={regPassword}
                    onChange={(e) => setRegPassword(e.target.value)}
                    required
                  />
                  <button
                    type="button"
                    className="officer-input-eye-btn"
                    onClick={() => setShowRegPassword(!showRegPassword)}
                  >
                    {showRegPassword ? <EyeOff size={18} /> : <Eye size={18} />}
                  </button>
                </div>
              </div>

              {/* Confirm Password */}
              <div className="officer-form-group">
                <label className="officer-form-label">
                  {isMr ? 'कन्फर्म पासवर्ड' : 'Confirm Password'}
                  <span className="required-star">*</span>
                </label>
                <div className="officer-input-wrapper">
                  <span className="officer-input-icon">
                    <Lock size={18} />
                  </span>
                  <input
                    type={showRegConfirmPassword ? 'text' : 'password'}
                    name="reg_officer_confirm_password"
                    autoComplete="new-password"
                    className="officer-input"
                    placeholder={isMr ? 'पासवर्ड पुन्हा टाका' : 'Re-enter password'}
                    value={regConfirmPassword}
                    onChange={(e) => setRegConfirmPassword(e.target.value)}
                    required
                  />
                  <button
                    type="button"
                    className="officer-input-eye-btn"
                    onClick={() => setShowRegConfirmPassword(!showRegConfirmPassword)}
                  >
                    {showRegConfirmPassword ? <EyeOff size={18} /> : <Eye size={18} />}
                  </button>
                </div>
              </div>

              {/* Register Action Button */}
              <button
                type="submit"
                className="officer-submit-btn"
                disabled={loading}
              >
                <span>
                  {loading
                    ? (isMr ? 'नोंदणी होत आहे...' : 'Registering...')
                    : isPhoneVerified
                      ? (isMr ? 'नोंदणी पूर्ण करा व लॉगिन व्हा' : 'Complete Registration & Login')
                      : (isMr ? 'मोबाईल नंबर पडताळा व खाते उघडा' : 'Verify Mobile & Open Account')}
                </span>
                <ArrowRight size={18} />
              </button>

              {/* Bottom Switch Link to Login */}
              <div className="officer-switch-footer">
                <span>{isMr ? 'आधीच खाते आहे?' : 'Already have an account?'} </span>
                <button
                  type="button"
                  className="officer-switch-link"
                  onClick={() => switchMode('login')}
                >
                  {isMr ? 'लॉगिन करा' : 'Sign In'}
                </button>
              </div>
            </form>
          )}

          {/* ================= BOTTOM FLUID WAVES ================= */}
          <div className="officer-bottom-wave-footer">
            <svg
              className="officer-bottom-svg"
              viewBox="0 0 440 60"
              fill="none"
              preserveAspectRatio="none"
            >
              <path d="M -10 60 C 35 22, 105 28, 175 60 Z" fill="#fbb06e" />
              <path d="M -10 60 C 22 12, 75 28, 125 60 Z" fill="#0c274c" />
              <path d="M 270 60 C 330 18, 395 10, 450 60 Z" fill="#fb923c" />
              <path d="M 305 60 C 355 20, 405 12, 450 60 Z" fill="#ff5500" />
            </svg>
          </div>

        </div>

      </div>

      {/* ================= POPUP: NUMBER VERIFICATION OTP MODAL (SHOWN ONLY ON ACCOUNT OPENING) ================= */}
      {isOtpModalOpen && (
        <div className="officer-modal-overlay" onClick={() => setIsOtpModalOpen(false)}>
          <div className="officer-modal-card" onClick={(e) => e.stopPropagation()}>
            <div className="officer-modal-header">
              <div className="officer-modal-title-group">
                <div className="officer-modal-title">
                  <span>🔐</span>
                  <span>{isMr ? 'मोबाईल नंबर पडताळणी' : 'Mobile Verification'}</span>
                </div>
                <div className="officer-modal-subtitle">
                  {isMr ? (
                    <>
                      <strong>+91 {regPhone}</strong> या क्रमांकावर Dove SMS द्वारे ६-अंकी OTP पाठवला आहे.
                    </>
                  ) : (
                    <>
                      Enter the 6-digit verification code sent via Dove SMS to <strong>+91 {regPhone}</strong>.
                    </>
                  )}
                </div>
              </div>
              <button
                type="button"
                className="officer-modal-close-btn"
                onClick={() => setIsOtpModalOpen(false)}
              >
                <X size={16} />
              </button>
            </div>

            {error && (
              <div className="officer-alert-banner officer-alert-error" style={{ marginBottom: '12px' }}>
                <AlertCircle size={16} />
                <span>{error}</span>
              </div>
            )}

            {/* 6 Square OTP Inputs */}
            <form onSubmit={handleVerifyRegistrationOtp}>
              <div className="officer-form-group">
                <div className="officer-otp-boxes-grid" onPaste={handleOtpPaste}>
                  {otpDigits.map((digit, index) => (
                    <input
                      key={index}
                      ref={(el) => (otpRefs.current[index] = el)}
                      type="text"
                      inputMode="numeric"
                      maxLength={1}
                      className="officer-otp-box"
                      value={digit}
                      onChange={(e) => handleOtpChange(index, e.target.value)}
                      onKeyDown={(e) => handleOtpKeyDown(index, e)}
                      autoFocus={index === 0}
                    />
                  ))}
                </div>
              </div>

              {/* Verify Action Button */}
              <button
                type="submit"
                className="officer-submit-btn"
                disabled={isVerifyingOtp || otpDigits.join('').length !== 6}
                style={{ marginTop: '14px' }}
              >
                {isVerifyingOtp ? (
                  <>
                    <Loader2 size={16} className="admin-spin" />
                    <span>{isMr ? 'पडताळणी होत आहे...' : 'Verifying...'}</span>
                  </>
                ) : (
                  <>
                    <span>{isMr ? 'पडताळणी पूर्ण करा' : 'Verify & Continue'}</span>
                    <ArrowRight size={18} />
                  </>
                )}
              </button>

              {/* Resend Timer Card */}
              <div className="officer-resend-card" style={{ marginTop: '14px' }}>
                <Clock size={16} className="officer-resend-icon" />
                <div className="officer-resend-content">
                  <div>
                    <span>{isMr ? 'SMS आला नाही?' : "Didn't receive SMS?"} </span>
                    <button
                      type="button"
                      className="officer-resend-action-btn"
                      onClick={() => handleSendRegistrationOtp()}
                      disabled={!canResendOtp || isSendingOtp}
                    >
                      {isMr ? 'पुन्हा पाठवा (Resend)' : 'Resend OTP'}
                    </button>
                  </div>
                  <div className="officer-resend-timer-txt">
                    {canResendOtp
                      ? (isMr ? 'तुम्ही आता पुन्हा OTP पाठवू शकता.' : 'You can resend OTP now.')
                      : (isMr
                        ? `${Math.floor(otpTimer / 60)}:${otpTimer % 60 < 10 ? '0' : ''}${otpTimer % 60} मध्ये पुन्हा पाठवू शकता.`
                        : `Resend available in ${Math.floor(otpTimer / 60)}:${otpTimer % 60 < 10 ? '0' : ''}${otpTimer % 60}`)}
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
      )}

    </div>
  );
}
