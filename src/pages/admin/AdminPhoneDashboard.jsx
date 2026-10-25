import React, { useState, useEffect, useMemo, useCallback } from 'react';
import { useAuth } from '../../hooks/useAuth';
import apiClient from '../../api/axios';
import './AdminPhoneDashboard.css';
import AdminPhoneChatbot from './AdminPhoneChatbot';
import {
  Menu,
  Shield,
  ShieldCheck,
  Users,
  Vote,
  ClipboardCheck,
  AlertTriangle,
  MapPin,
  Clock,
  Search,
  CheckCircle2,
  XCircle,
  Phone,
  MessageSquare,
  LogOut,
  ChevronRight,
  TrendingUp,
  Sparkles,
  RefreshCw,
  FolderKanban,
  UserCheck,
  X,
  Database,
  Building,
  Bell,
  Loader2,
  Check,
  Calendar
} from 'lucide-react';

// Live IST Clock
const LiveISTClock = React.memo(function LiveISTClock({ isMr }) {
  const [time, setTime] = useState(() => new Date());

  useEffect(() => {
    const timer = setInterval(() => setTime(new Date()), 1000);
    return () => clearInterval(timer);
  }, []);

  const formatted = useMemo(() => {
    try {
      return time.toLocaleTimeString(isMr ? 'mr-IN' : 'en-IN', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true,
      });
    } catch {
      return time.toTimeString().split(' ')[0];
    }
  }, [time, isMr]);

  return (
    <div className="admin-clock-display">
      <Clock size={12} />
      <span>{formatted}</span>
    </div>
  );
});

export default function AdminPhoneDashboard() {
  const { user, logout } = useAuth();

  // Language state
  const [lang, setLang] = useState(() => localStorage.getItem('officer_portal_lang') || 'mr');
  const isMr = lang === 'mr';

  // Navigation state
  const [activeTab, setActiveTab] = useState('dashboard');
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  const [isChatbotOpen, setIsChatbotOpen] = useState(false);

  // Search & Filter
  const [searchQuery, setSearchQuery] = useState('');
  const [issueFilter, setIssueFilter] = useState('ALL');
  const [approvalFilter, setApprovalFilter] = useState('ALL');
  const [toastMsg, setToastMsg] = useState('');

  // ===================== REAL DATABASE STATES =====================
  const [overview, setOverview] = useState(null);
  const [projects, setProjects] = useState([]);
  const [approvals, setApprovals] = useState([]);
  const [issues, setIssues] = useState([]);
  const [booths, setBooths] = useState([]);
  const [attendances, setAttendances] = useState([]);
  const [backups, setBackups] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [actionLoadingId, setActionLoadingId] = useState(null);

  // Show Toast
  const showToast = useCallback((msg) => {
    setToastMsg(msg);
    setTimeout(() => setToastMsg(''), 3000);
  }, []);

  // Fetch all real database data
  const fetchAllAdminData = useCallback(async (isManual = false) => {
    if (isManual) setRefreshing(true);
    try {
      const [
        overviewRes,
        projectsRes,
        submissionsRes,
        complaintsRes,
        boothsRes,
        attendanceRes,
        backupsRes
      ] = await Promise.allSettled([
        apiClient.get('/neta/overview'),
        apiClient.get('/admin/projects?per_page=50'),
        apiClient.get('/admin/submissions'),
        apiClient.get('/worker/complaints'),
        apiClient.get('/neta/booths'),
        apiClient.get('/admin/attendance?date=all'),
        apiClient.get('/admin/system/backups')
      ]);

      if (overviewRes.status === 'fulfilled' && overviewRes.value?.data?.success) {
        setOverview(overviewRes.value.data.data);
      }
      if (projectsRes.status === 'fulfilled') {
        const projData = projectsRes.value?.data?.data || projectsRes.value?.data || [];
        setProjects(Array.isArray(projData) ? projData : []);
      }
      if (submissionsRes.status === 'fulfilled' && submissionsRes.value?.data?.success) {
        setApprovals(submissionsRes.value.data.data || []);
      }
      if (complaintsRes.status === 'fulfilled' && complaintsRes.value?.data?.success) {
        setIssues(complaintsRes.value.data.data || []);
      }
      if (boothsRes.status === 'fulfilled' && boothsRes.value?.data?.success) {
        setBooths(boothsRes.value.data.data || []);
      }
      if (attendanceRes.status === 'fulfilled' && attendanceRes.value?.data?.success) {
        const attList = attendanceRes.value.data.data?.attendances || [];
        setAttendances(Array.isArray(attList) ? attList : []);
      }
      if (backupsRes.status === 'fulfilled' && backupsRes.value?.data?.success) {
        setBackups(backupsRes.value.data.data || []);
      }

      if (isManual) {
        showToast(isMr ? 'डेटाबेस थेट डेटा रिफ्रेश झाला!' : 'Database live data refreshed!');
      }
    } catch (err) {
      console.error('Error fetching admin data from DB:', err);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [isMr, showToast]);

  useEffect(() => {
    fetchAllAdminData();
  }, [fetchAllAdminData]);

  // Handle Approvals
  const handleApprove = async (id) => {
    setActionLoadingId(id);
    try {
      await apiClient.post(`/admin/submissions/${id}/approve`);
      setApprovals((prev) =>
        prev.map((item) => (item.id === id ? { ...item, status: 'APPROVED' } : item))
      );
      showToast(isMr ? `सर्व्हे #${id} यशस्वीरीत्या मंजूर केला!` : `Survey #${id} approved!`);
    } catch (e) {
      console.error('Approve failed:', e);
      showToast(isMr ? 'मंजूरी करताना त्रुटी आली' : 'Failed to approve');
    } finally {
      setActionLoadingId(null);
    }
  };

  const handleReject = async (id) => {
    setActionLoadingId(id);
    try {
      await apiClient.post(`/admin/submissions/${id}/reject`, { reason: 'प्रशासक द्वारे नाकारले' });
      setApprovals((prev) =>
        prev.map((item) => (item.id === id ? { ...item, status: 'REJECTED' } : item))
      );
      showToast(isMr ? `सर्व्हे #${id} नाकारण्यात आला.` : `Survey #${id} rejected.`);
    } catch (e) {
      console.error('Reject failed:', e);
      showToast(isMr ? 'नाकारताना त्रुटी आली' : 'Failed to reject');
    } finally {
      setActionLoadingId(null);
    }
  };

  // Handle Grievance Status Update
  const handleUpdateComplaint = async (id, newStatus = 'Resolved') => {
    setActionLoadingId(id);
    try {
      await apiClient.patch(`/worker/complaints/${id}/status`, { status: newStatus });
      setIssues((prev) =>
        prev.map((item) =>
          item.id === id || item.complaint_code === id ? { ...item, status: newStatus } : item
        )
      );
      showToast(isMr ? `तक्रार स्थिती अद्यतनित केली (${newStatus})!` : `Complaint status updated (${newStatus})!`);
    } catch (e) {
      console.error('Complaint status update failed:', e);
      showToast(isMr ? 'तक्रार अपडेट करताना त्रुटी आली' : 'Failed to update complaint');
    } finally {
      setActionLoadingId(null);
    }
  };

  // Handle Backup Trigger
  const handleCreateBackup = async () => {
    setActionLoadingId('backup');
    try {
      const res = await apiClient.post('/admin/system/backups');
      if (res.data?.success) {
        showToast(isMr ? 'नवीन डेटाबेस बॅकअप यशस्वीरीत्या तयार झाला!' : 'Database backup created successfully!');
        const bRes = await apiClient.get('/admin/system/backups');
        if (bRes.data?.data) setBackups(bRes.data.data);
      }
    } catch (e) {
      console.error('Backup creation failed:', e);
      showToast(isMr ? 'बॅकअप घेताना त्रुटी आली' : 'Failed to create backup');
    } finally {
      setActionLoadingId(null);
    }
  };

  const toggleLang = () => {
    const nextLang = lang === 'mr' ? 'en' : 'mr';
    setLang(nextLang);
    localStorage.setItem('officer_portal_lang', nextLang);
  };

  // Derived Metrics from Database
  const totalVoters = useMemo(() => {
    if (overview?.profile?.total_voters) return overview.profile.total_voters;
    return booths.reduce((acc, b) => acc + (Number(b.total_voters) || 0), 0);
  }, [overview, booths]);

  const totalBoothsCount = useMemo(() => {
    return booths.length || overview?.profile?.total_booths || 0;
  }, [booths, overview]);

  const pendingApprovalsCount = useMemo(() => {
    return approvals.filter(
      (a) => a.status === 'PENDING_APPROVAL' || a.status === 'PENDING'
    ).length;
  }, [approvals]);

  const activeWorkersCount = useMemo(() => {
    if (overview?.profile?.active_karyakartas_today) {
      return overview.profile.active_karyakartas_today;
    }
    const todayActive = attendances.filter(
      (a) => a.status === 'Present' || a.status === 'Completed'
    );
    return todayActive.length || attendances.length || 0;
  }, [overview, attendances]);

  // Group Booths by Village for Demographics
  const villagesList = useMemo(() => {
    const map = new Map();
    booths.forEach((b) => {
      const vName = b.village_mr || b.village_en || `बूथ ${b.booth_no}`;
      if (!map.has(vName)) {
        map.set(vName, {
          nameMr: b.village_mr || b.village_en || vName,
          nameEn: b.village_en || b.village_mr || vName,
          taluka: b.taluka_mr || b.taluka_en || '',
          population: Number(b.total_population) || 0,
          voters: Number(b.total_voters) || 0,
          boothsCount: 1,
          incharge: b.incharge_name || '',
          phone: b.incharge_phone || '',
          isSensitive: b.is_sensitive || false,
        });
      } else {
        const item = map.get(vName);
        item.population += Number(b.total_population) || 0;
        item.voters += Number(b.total_voters) || 0;
        item.boothsCount += 1;
        if (b.is_sensitive) item.isSensitive = true;
      }
    });
    return Array.from(map.values());
  }, [booths]);

  // Filtered villages
  const filteredVillages = useMemo(() => {
    if (!searchQuery.trim()) return villagesList;
    const q = searchQuery.toLowerCase();
    return villagesList.filter(
      (v) =>
        v.nameMr.toLowerCase().includes(q) ||
        v.nameEn.toLowerCase().includes(q) ||
        v.taluka.toLowerCase().includes(q) ||
        v.incharge.toLowerCase().includes(q)
    );
  }, [villagesList, searchQuery]);

  // Filtered Projects
  const filteredProjects = useMemo(() => {
    if (!searchQuery.trim()) return projects;
    const q = searchQuery.toLowerCase();
    return projects.filter(
      (p) =>
        (p.name && p.name.toLowerCase().includes(q)) ||
        (p.project_type && p.project_type.toLowerCase().includes(q)) ||
        (p.status && p.status.toLowerCase().includes(q))
    );
  }, [projects, searchQuery]);

  // Filtered Approvals
  const filteredApprovals = useMemo(() => {
    let list = approvals;
    if (approvalFilter !== 'ALL') {
      list = list.filter((a) => a.status === approvalFilter);
    }
    if (searchQuery.trim()) {
      const q = searchQuery.toLowerCase();
      list = list.filter(
        (a) =>
          (a.citizen_name_mr && a.citizen_name_mr.toLowerCase().includes(q)) ||
          (a.submission_code && a.submission_code.toLowerCase().includes(q)) ||
          (a.submitted_by_name && a.submitted_by_name.toLowerCase().includes(q)) ||
          (a.village_mr && a.village_mr.toLowerCase().includes(q))
      );
    }
    return list;
  }, [approvals, approvalFilter, searchQuery]);

  // Filtered Issues (Complaints)
  const filteredIssues = useMemo(() => {
    let list = issues;
    if (issueFilter !== 'ALL') {
      list = list.filter((i) => i.status === issueFilter);
    }
    if (searchQuery.trim()) {
      const q = searchQuery.toLowerCase();
      list = list.filter(
        (i) =>
          (i.name && i.name.toLowerCase().includes(q)) ||
          (i.complaint_code && i.complaint_code.toLowerCase().includes(q)) ||
          (i.category && i.category.toLowerCase().includes(q)) ||
          (i.desc && i.desc.toLowerCase().includes(q))
      );
    }
    return list;
  }, [issues, issueFilter, searchQuery]);

  // Sensitive Booths from DB
  const sensitiveBoothsList = useMemo(() => {
    return booths.filter((b) => b.is_sensitive || Number(b.coverage_pct) < 60);
  }, [booths]);

  return (
    <div className="admin-phone-root">
      {/* ================= STICKY HEADER ================= */}
      <header className="admin-phone-header">
        <div className="admin-phone-header-left">
          <button
            type="button"
            className="admin-hamburger-btn"
            onClick={() => setIsDrawerOpen(true)}
            title={isMr ? 'मेनू उघडा' : 'Open Menu'}
          >
            <Menu size={20} />
          </button>
          <div className="admin-brand-info">
            <div className="admin-brand-title">
              <span className="admin-header-logo-badge">🛡️</span>
              <span className="admin-brand-name">
                <span className="highlight-orange">
                  {isMr ? 'प्रशासक नियंत्रण कक्ष' : 'Admin Dashboard'}
                </span>
              </span>
            </div>
            <div className="admin-brand-sub">

              <span>• {user?.full_name || user?.name || 'Chief Administrator'}</span>
            </div>
          </div>
        </div>

        <div className="admin-phone-header-right">
          <button
            type="button"
            className="admin-lang-btn"
            onClick={toggleLang}
          >
            {isMr ? 'EN' : 'मराठी'}
          </button>
          <button
            type="button"
            className="admin-logout-btn"
            onClick={logout}
            title={isMr ? 'लॉगआउट' : 'Logout'}
          >
            <LogOut size={16} />
          </button>
        </div>
      </header>

      {/* ================= SUB-BAR WITH LIVE CLOCK & REFRESH ================= */}
      <div className="admin-subbar">
        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
          <LiveISTClock isMr={isMr} />
          <span style={{ color: 'rgba(255, 255, 255, 0.7)', fontSize: '10.5px', fontWeight: '600' }}>
            {isMr ? 'भारतीय प्रमाणवेळ' : 'IST Time'}
          </span>
        </div>

        <button
          type="button"
          onClick={() => fetchAllAdminData(true)}
          style={{
            background: 'rgba(255, 255, 255, 0.12)',
            border: 'none',
            borderRadius: '6px',
            color: '#ffffff',
            padding: '3px 8px',
            fontSize: '10.5px',
            fontWeight: '700',
            cursor: 'pointer',
            display: 'flex',
            alignItems: 'center',
            gap: '4px',
          }}
          title={isMr ? 'डेटाबेस रिफ्रेश करा' : 'Refresh DB'}
        >
          <RefreshCw size={12} className={refreshing ? 'admin-spin' : ''} />

        </button>
      </div>

      {/* ================= TOAST NOTIFICATION ================= */}
      {toastMsg && (
        <div
          style={{
            position: 'fixed',
            top: '80px',
            left: '50%',
            transform: 'translateX(-50%)',
            background: '#071e3b',
            color: '#ffffff',
            padding: '10px 18px',
            borderRadius: '24px',
            fontSize: '12px',
            fontWeight: '750',
            zIndex: 999,
            boxShadow: '0 6px 20px rgba(0,0,0,0.35)',
            border: '1px solid rgba(255, 82, 0, 0.4)',
            display: 'flex',
            alignItems: 'center',
            gap: '8px',
          }}
        >
          <CheckCircle2 size={16} style={{ color: '#10b981' }} />
          <span>{toastMsg}</span>
        </div>
      )}

      {/* ================= SLIDE-OUT DRAWER ================= */}
      {isDrawerOpen && (
        <>
          <div className="admin-drawer-overlay" onClick={() => setIsDrawerOpen(false)} />
          <div className="admin-drawer-content">
            <div className="admin-drawer-header">
              <button
                type="button"
                className="admin-drawer-close"
                onClick={() => setIsDrawerOpen(false)}
              >
                <X size={16} />
              </button>
              <div className="admin-drawer-user">
                <div className="admin-drawer-avatar">
                  {(user?.full_name || 'A').charAt(0).toUpperCase()}
                  <span className="admin-badge-lock">👑</span>
                </div>
                <div className="admin-drawer-info">
                  <h4>{user?.full_name || 'Chief Administrator'}</h4>
                  <p>{user?.email || 'admin@gmail.com'}</p>
                  <span className="admin-drawer-role-pill">
                    ⚡ SUPER ADMIN (LIVE DB)
                  </span>
                </div>
              </div>
            </div>

            <div className="admin-drawer-nav">
              <button
                type="button"
                className={`admin-drawer-item ${activeTab === 'dashboard' ? 'active' : ''}`}
                onClick={() => { setActiveTab('dashboard'); setIsDrawerOpen(false); }}
              >
                <span style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                  <span>📊</span> {isMr ? 'मुख्य डॅशबोर्ड' : 'Dashboard'}
                </span>
                <ChevronRight size={16} />
              </button>

              <button
                type="button"
                className={`admin-drawer-item ${activeTab === 'projects' ? 'active' : ''}`}
                onClick={() => { setActiveTab('projects'); setIsDrawerOpen(false); }}
              >
                <span style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                  <span>📁</span> {isMr ? 'प्रकल्प व मतदार यादी' : 'Projects & Voters'}
                </span>
                <span className="admin-badge-count">{projects.length}</span>
              </button>

              <button
                type="button"
                className={`admin-drawer-item ${activeTab === 'attendance' ? 'active' : ''}`}
                onClick={() => { setActiveTab('attendance'); setIsDrawerOpen(false); }}
              >
                <span style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                  <span>📍</span> {isMr ? 'कार्यकर्ते हजेरी ट्रॅकिंग' : 'Worker Attendance'}
                </span>
                <span className="admin-badge-count">{attendances.length}</span>
              </button>

              <button
                type="button"
                className={`admin-drawer-item ${activeTab === 'approvals' ? 'active' : ''}`}
                onClick={() => { setActiveTab('approvals'); setIsDrawerOpen(false); }}
              >
                <span style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                  <span>📝</span> {isMr ? 'सर्व्हे मंजुऱ्या' : 'Survey Approvals'}
                </span>
                {pendingApprovalsCount > 0 && (
                  <span style={{ background: '#ef4444', color: '#fff', fontSize: '10px', padding: '2px 6px', borderRadius: '10px', fontWeight: '800' }}>
                    {pendingApprovalsCount}
                  </span>
                )}
              </button>

              <button
                type="button"
                className={`admin-drawer-item ${activeTab === 'issues' ? 'active' : ''}`}
                onClick={() => { setActiveTab('issues'); setIsDrawerOpen(false); }}
              >
                <span style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                  <span>⚠️</span> {isMr ? 'नागरी तक्रारी निवारण' : 'Civic Grievances'}
                </span>
                <span className="admin-badge-count">{issues.length}</span>
              </button>

              <button
                type="button"
                className={`admin-drawer-item ${activeTab === 'master_data' ? 'active' : ''}`}
                onClick={() => { setActiveTab('master_data'); setIsDrawerOpen(false); }}
              >
                <span style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                  <span>🏘️</span> {isMr ? 'गावनिहाय लोकसंख्या व मतदार' : 'Village Demographics'}
                </span>
                <span className="admin-badge-count">{villagesList.length}</span>
              </button>

              <button
                type="button"
                className={`admin-drawer-item ${activeTab === 'system' ? 'active' : ''}`}
                onClick={() => { setActiveTab('system'); setIsDrawerOpen(false); }}
              >
                <span style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                  <span>🛡️</span> {isMr ? 'डेटाबेस सुरक्षा व बॅकअप' : 'System & Backup'}
                </span>
                <ChevronRight size={16} />
              </button>
            </div>

            <div className="admin-drawer-footer">
              <button type="button" className="admin-drawer-logout" onClick={logout}>
                <LogOut size={16} />
                <span>{isMr ? 'प्रणालीमधून बाहेर पडा (Logout)' : 'Logout of Session'}</span>
              </button>
            </div>
          </div>
        </>
      )}

      {/* ================= MAIN CONTENT ================= */}
      <main className="admin-phone-content">

        {loading ? (
          <div style={{ textAlign: 'center', padding: '60px 20px', color: '#64748b' }}>
            <Loader2 size={32} className="admin-spin" style={{ color: '#ff5200', margin: '0 auto 12px' }} />
            <div style={{ fontSize: '13px', fontWeight: '750' }}>
              {isMr ? 'डेटाबेसवरून माहिती लोड होत आहे...' : 'Loading data from database...'}
            </div>
          </div>
        ) : (
          <>
            {/* ================= TAB 1: DASHBOARD ================= */}
            {activeTab === 'dashboard' && (
              <>
                {/* Top 6 Metric Cards directly from DB */}
                <div className="admin-metrics-grid">
                  <div className="admin-metric-card" style={{ borderTop: '3px solid #ff5200' }}>
                    <div className="admin-metric-header">
                      <div className="admin-metric-icon admin-metric-icon-hero">
                        <Users size={18} />
                      </div>
                      <span className="admin-metric-tag" style={{ background: '#fff7ed', color: '#c2410c', border: '1px solid #fed7aa' }}>
                        थेट DB
                      </span>
                    </div>
                    <div className="admin-metric-value">{totalVoters.toLocaleString('en-IN')}</div>
                    <div className="admin-metric-label">{isMr ? 'एकूण मतदार संख्या' : 'Total Voters'}</div>
                  </div>

                  <div className="admin-metric-card" style={{ borderTop: '3px solid #0c2b54' }}>
                    <div className="admin-metric-header">
                      <div className="admin-metric-icon" style={{ background: 'linear-gradient(135deg, #1d4b82 0%, #0c2b54 100%)', color: '#ffffff', boxShadow: '0 4px 10px rgba(12, 43, 84, 0.25)' }}>
                        <Vote size={18} />
                      </div>
                      <span className="admin-metric-tag" style={{ background: '#f1f5f9', color: '#1e3a8a', border: '1px solid #cbd5e1' }}>
                        १००% मॅप
                      </span>
                    </div>
                    <div className="admin-metric-value">{totalBoothsCount}</div>
                    <div className="admin-metric-label">{isMr ? 'एकूण मतदान केंद्र (बूथ)' : 'Total Booths'}</div>
                  </div>

                  <div className="admin-metric-card">
                    <div className="admin-metric-header">
                      <div className="admin-metric-icon" style={{ background: '#fff1f2', color: '#e11d48' }}>
                        <ClipboardCheck size={18} />
                      </div>
                      <span className="admin-metric-tag" style={{ background: '#fee2e2', color: '#991b1b' }}>
                        {pendingApprovalsCount > 0 ? (isMr ? 'तातडीचे' : 'Pending') : (isMr ? 'पूर्ण' : 'All Clear')}
                      </span>
                    </div>
                    <div className="admin-metric-value">{pendingApprovalsCount}</div>
                    <div className="admin-metric-label">{isMr ? 'प्रलंबित सर्व्हे मंजुऱ्या' : 'Pending Approvals'}</div>
                  </div>

                  <div className="admin-metric-card">
                    <div className="admin-metric-header">
                      <div className="admin-metric-icon" style={{ background: '#f0fdf4', color: '#16a34a' }}>
                        <UserCheck size={18} />
                      </div>
                      <span className="admin-metric-tag" style={{ background: '#dcfce7', color: '#15803d' }}>
                        सक्रिय
                      </span>
                    </div>
                    <div className="admin-metric-value">{activeWorkersCount}</div>
                    <div className="admin-metric-label">{isMr ? 'सक्रिय कार्यकर्ते' : 'Active Field Workers'}</div>
                  </div>

                  <div className="admin-metric-card">
                    <div className="admin-metric-header">
                      <div className="admin-metric-icon" style={{ background: '#fffbeb', color: '#d97706' }}>
                        <AlertTriangle size={18} />
                      </div>
                      <span className="admin-metric-tag" style={{ background: '#fef3c7', color: '#92400e' }}>
                        नोंदणीकृत
                      </span>
                    </div>
                    <div className="admin-metric-value">{issues.length}</div>
                    <div className="admin-metric-label">{isMr ? 'नागरी तक्रारी' : 'Civic Grievances'}</div>
                  </div>

                  <div className="admin-metric-card">
                    <div className="admin-metric-header">
                      <div className="admin-metric-icon" style={{ background: '#ecfeff', color: '#0891b2' }}>
                        <Building size={18} />
                      </div>
                      <span className="admin-metric-tag" style={{ background: '#cffafe', color: '#155e75' }}>
                        {overview?.profile?.district_mr || (isMr ? 'कार्यक्षेत्र' : 'Constituency')}
                      </span>
                    </div>
                    <div className="admin-metric-value">{villagesList.length}</div>
                    <div className="admin-metric-label">{isMr ? 'समाविष्ट गावे / प्रभाग' : 'Villages / Wards'}</div>
                  </div>
                </div>

                {/* Quick Actions Grid */}
                <div className="admin-quick-actions">
                  <button
                    type="button"
                    className="admin-action-btn"
                    onClick={() => setActiveTab('projects')}
                  >
                    <FolderKanban size={22} style={{ color: '#2563eb' }} />
                    <span className="label">{isMr ? 'प्रकल्प' : 'Projects'}</span>
                  </button>
                  <button
                    type="button"
                    className="admin-action-btn"
                    onClick={() => setActiveTab('approvals')}
                  >
                    <ClipboardCheck size={22} style={{ color: '#10b981' }} />
                    <span className="label">{isMr ? 'मंजुरी' : 'Approvals'}</span>
                  </button>
                  <button
                    type="button"
                    className="admin-action-btn"
                    onClick={() => setActiveTab('attendance')}
                  >
                    <UserCheck size={22} style={{ color: '#7c3aed' }} />
                    <span className="label">{isMr ? 'हजेरी' : 'Attendance'}</span>
                  </button>
                  <button
                    type="button"
                    className="admin-action-btn"
                    onClick={() => setIsChatbotOpen(true)}
                  >
                    <Sparkles size={22} style={{ color: '#ea580c' }} />
                    <span className="label">{isMr ? 'AI रिपोर्ट' : 'AI Report'}</span>
                  </button>
                </div>

                {/* Sensitive Booths & High Priority Alerts directly from DB */}
                <div className="admin-section-card">
                  <div className="admin-section-header">
                    <div className="admin-section-title">
                      <AlertTriangle size={18} style={{ color: '#ea580c' }} />
                      <span>{isMr ? 'संवेदनशील बूथ व तातडीचे अलर्ट' : 'Sensitive Booth Alerts'}</span>
                    </div>
                    <span className="admin-badge-count">
                      {sensitiveBoothsList.length} {isMr ? 'बूथ' : 'Booths'}
                    </span>
                  </div>

                  {sensitiveBoothsList.length === 0 ? (
                    <div style={{ padding: '16px', textAlign: 'center', color: '#166534', background: '#f0fdf4', borderRadius: '8px', fontSize: '12px', fontWeight: '750' }}>
                      ✓ {isMr ? 'सर्व मतदान केंद्र सुरळीत कार्यरत आहेत!' : 'All polling booths are operating smoothly!'}
                    </div>
                  ) : (
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                      {sensitiveBoothsList.slice(0, 4).map((b) => (
                        <div
                          key={b.id}
                          style={{
                            background: '#fff7ed',
                            borderLeft: '4px solid #ea580c',
                            padding: '9px 12px',
                            borderRadius: '6px',
                          }}
                        >
                          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                            <div style={{ fontSize: '12px', fontWeight: '800', color: '#9a3412' }}>
                              🚨 {b.booth_name_mr || `बूथ क्र. ${b.booth_no}`}
                            </div>
                            <span style={{ fontSize: '10px', background: '#fee2e2', color: '#991b1b', padding: '1px 5px', borderRadius: '4px', fontWeight: '800' }}>
                              {b.coverage_pct}% कव्हरेज
                            </span>
                          </div>
                          <div style={{ fontSize: '11px', color: '#7c2d12', marginTop: '2px' }}>
                            {b.village_mr} • इनचार्ज: {b.incharge_name || 'नेमणूक प्रलंबित'}
                            {b.incharge_phone && ` (${b.incharge_phone})`}
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </div>

                {/* Village Population Snapshot directly from DB */}
                <div className="admin-section-card">
                  <div className="admin-section-header">
                    <div className="admin-section-title">
                      <Building size={18} style={{ color: '#0284c7' }} />
                      <span>{isMr ? 'गावनिहाय लोकसंख्या व मतदार सारांश' : 'Village Demographics Summary'}</span>
                    </div>
                    <button
                      type="button"
                      onClick={() => setActiveTab('master_data')}
                      style={{ background: 'none', border: 'none', color: '#2563eb', fontSize: '11px', fontWeight: '800', cursor: 'pointer' }}
                    >
                      {isMr ? 'सर्व पहा →' : 'View All →'}
                    </button>
                  </div>

                  <div style={{ display: 'flex', flexDirection: 'column', gap: '6px' }}>
                    {villagesList.slice(0, 6).map((v, idx) => (
                      <div
                        key={idx}
                        style={{
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'space-between',
                          padding: '8px 10px',
                          background: '#f8fafc',
                          borderRadius: '8px',
                          border: '1px solid #f1f5f9',
                        }}
                      >
                        <div>
                          <div style={{ fontSize: '12px', fontWeight: '800', color: '#0f172a' }}>{v.nameMr}</div>
                          <div style={{ fontSize: '10.5px', color: '#64748b' }}>
                            लोकसंख्या: <strong>{v.population ? v.population.toLocaleString('en-IN') : '-'}</strong>
                            {v.taluka && ` • ${v.taluka}`}
                          </div>
                        </div>
                        <div style={{ textAlign: 'right' }}>
                          <div style={{ fontSize: '12px', fontWeight: '900', color: '#2563eb' }}>
                            {v.voters ? v.voters.toLocaleString('en-IN') : '-'}
                          </div>
                          <div style={{ fontSize: '10px', color: '#64748b' }}>मतदार ({v.boothsCount} बूथ)</div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              </>
            )}

            {/* ================= TAB 2: PROJECTS & CAMPAIGNS ================= */}
            {activeTab === 'projects' && (
              <>
                <div className="admin-section-card">
                  <div className="admin-section-header">
                    <div className="admin-section-title">
                      <Search size={18} style={{ color: '#2563eb' }} />
                      <span>{isMr ? 'प्रकल्प शोध' : 'Search Projects'}</span>
                    </div>
                  </div>
                  <input
                    type="text"
                    placeholder={isMr ? 'प्रकल्पाचे नाव किंवा प्रकार शोधा...' : 'Search by project name or type...'}
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    style={{
                      width: '100%',
                      padding: '10px 14px',
                      borderRadius: '10px',
                      border: '1.5px solid #cbd5e1',
                      fontSize: '12.5px',
                    }}
                  />
                </div>

                <div className="admin-section-card">
                  <div className="admin-section-header">
                    <div className="admin-section-title">
                      <FolderKanban size={18} style={{ color: '#7c3aed' }} />
                      <span>{isMr ? 'डेटाबेस प्रकल्प यादी' : 'Database Projects'}</span>
                    </div>
                    <span className="admin-badge-count">{filteredProjects.length} प्रकल्प</span>
                  </div>

                  {filteredProjects.length === 0 ? (
                    <div style={{ textAlign: 'center', padding: '30px 10px', color: '#64748b' }}>
                      <div style={{ fontSize: '13px', fontWeight: '750' }}>
                        {isMr ? 'कोणतेही प्रकल्प आढळले नाहीत' : 'No projects found'}
                      </div>
                    </div>
                  ) : (
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
                      {filteredProjects.map((p) => (
                        <div
                          key={p.uuid || p.id}
                          style={{
                            border: '1px solid #e2e8f0',
                            borderRadius: '10px',
                            padding: '12px',
                            background: '#ffffff',
                          }}
                        >
                          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                            <span style={{ fontSize: '13px', fontWeight: '800', color: '#0f172a' }}>
                              {p.name}
                            </span>
                            <span
                              style={{
                                fontSize: '10px',
                                padding: '2px 8px',
                                borderRadius: '4px',
                                fontWeight: '800',
                                background: p.status === 'ACTIVE' ? '#dcfce7' : p.status === 'COMPLETED' ? '#e0f2fe' : '#fef3c7',
                                color: p.status === 'ACTIVE' ? '#166534' : p.status === 'COMPLETED' ? '#075985' : '#854d0e',
                              }}
                            >
                              {p.status}
                            </span>
                          </div>

                          <div style={{ fontSize: '11px', color: '#64748b', marginTop: '4px' }}>
                            प्रकार: <strong>{p.project_type || 'GENERAL'}</strong>
                            {p.assigned_members_count > 0 && ` • नियुक्त सदस्य: ${p.assigned_members_count}`}
                          </div>

                          {p.description && (
                            <div style={{ fontSize: '11px', color: '#475569', marginTop: '4px', fontStyle: 'italic' }}>
                              {p.description}
                            </div>
                          )}

                          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginTop: '8px', fontSize: '10px', color: '#94a3b8' }}>
                            <span>व्याप्ती: {p.geographic_scope || 'विधानसभा'}</span>
                            <span>तयार: {p.created_at ? new Date(p.created_at).toLocaleDateString() : '-'}</span>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              </>
            )}

            {/* ================= TAB 3: ATTENDANCE ================= */}
            {activeTab === 'attendance' && (
              <>
                <div className="admin-section-card">
                  <div className="admin-section-header">
                    <div className="admin-section-title">
                      <UserCheck size={18} style={{ color: '#10b981' }} />
                      <span>{isMr ? 'उपस्थिती सारांश (थेट DB)' : 'Live DB Attendance'}</span>
                    </div>
                    <span className="admin-badge-count">{attendances.length} नोंदी</span>
                  </div>

                  <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '8px', textAlign: 'center' }}>
                    <div style={{ background: '#f0fdf4', padding: '8px', borderRadius: '8px' }}>
                      <div style={{ fontSize: '16px', fontWeight: '900', color: '#16a34a' }}>
                        {attendances.filter((a) => a.status === 'Present' || a.status === 'Completed').length}
                      </div>
                      <div style={{ fontSize: '10px', color: '#15803d', fontWeight: '750' }}>{isMr ? 'हजर' : 'Present'}</div>
                    </div>
                    <div style={{ background: '#eff6ff', padding: '8px', borderRadius: '8px' }}>
                      <div style={{ fontSize: '16px', fontWeight: '900', color: '#2563eb' }}>
                        {attendances.filter((a) => a.punch_out_time && a.punch_out_time !== '-').length}
                      </div>
                      <div style={{ fontSize: '10px', color: '#1e40af', fontWeight: '750' }}>{isMr ? 'पूर्ण' : 'Completed'}</div>
                    </div>
                    <div style={{ background: '#fffbeb', padding: '8px', borderRadius: '8px' }}>
                      <div style={{ fontSize: '16px', fontWeight: '900', color: '#d97706' }}>
                        {attendances.length}
                      </div>
                      <div style={{ fontSize: '10px', color: '#b45309', fontWeight: '750' }}>{isMr ? 'एकूण' : 'Total'}</div>
                    </div>
                  </div>
                </div>

                {/* Live Database Workers on Duty */}
                <div className="admin-section-card">
                  <div className="admin-section-header">
                    <div className="admin-section-title">
                      <Users size={18} style={{ color: '#2563eb' }} />
                      <span>{isMr ? 'कार्यकर्ते हजेरी नोंदी' : 'Attendance Log Records'}</span>
                    </div>
                  </div>

                  {attendances.length === 0 ? (
                    <div style={{ textAlign: 'center', padding: '24px 10px', color: '#64748b' }}>
                      {isMr ? 'कोणतीही हजेरी नोंद आढळली नाही.' : 'No attendance records found.'}
                    </div>
                  ) : (
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                      {attendances.slice(0, 15).map((att) => (
                        <div
                          key={att.id}
                          style={{
                            background: '#f8fafc',
                            border: '1px solid #e2e8f0',
                            borderRadius: '10px',
                            padding: '10px 12px',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'space-between',
                          }}
                        >
                          <div style={{ flex: 1, minWidth: 0, paddingRight: '8px' }}>
                            <div style={{ fontSize: '12.5px', fontWeight: '800', color: '#0f172a' }}>
                              {att.user_name || 'अज्ञात कार्यकर्ता'}
                            </div>
                            <div style={{ fontSize: '10.5px', color: '#64748b' }}>
                              {att.user_role || 'WORKER'} • {att.date}
                            </div>
                            {att.address && (
                              <div style={{ fontSize: '10px', color: '#475569', marginTop: '2px', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                                📍 {att.address}
                              </div>
                            )}
                            <div style={{ display: 'flex', gap: '6px', marginTop: '4px' }}>
                              <span style={{ fontSize: '9.5px', background: '#dcfce7', color: '#166534', padding: '1px 5px', borderRadius: '4px', fontWeight: '750' }}>
                                ⏱️ इन: {att.punch_in_time || '-'}
                              </span>
                              {att.punch_out_time && att.punch_out_time !== '-' && (
                                <span style={{ fontSize: '9.5px', background: '#e0f2fe', color: '#0369a1', padding: '1px 5px', borderRadius: '4px', fontWeight: '750' }}>
                                  ⏱️ आऊट: {att.punch_out_time}
                                </span>
                              )}
                            </div>
                          </div>

                          {att.user_phone && (
                            <div style={{ display: 'flex', gap: '6px' }}>
                              <a
                                href={`tel:${att.user_phone}`}
                                style={{
                                  width: '32px',
                                  height: '32px',
                                  borderRadius: '8px',
                                  background: '#dcfce7',
                                  color: '#166534',
                                  display: 'flex',
                                  alignItems: 'center',
                                  justifyContent: 'center',
                                  textDecoration: 'none',
                                }}
                              >
                                <Phone size={15} />
                              </a>
                              <a
                                href={`https://wa.me/91${att.user_phone.replace(/\D/g, '')}`}
                                target="_blank"
                                rel="noreferrer"
                                style={{
                                  width: '32px',
                                  height: '32px',
                                  borderRadius: '8px',
                                  background: '#dcfce7',
                                  color: '#16a34a',
                                  display: 'flex',
                                  alignItems: 'center',
                                  justifyContent: 'center',
                                  textDecoration: 'none',
                                }}
                              >
                                <MessageSquare size={15} />
                              </a>
                            </div>
                          )}
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              </>
            )}

            {/* ================= TAB 4: APPROVALS ================= */}
            {activeTab === 'approvals' && (
              <div className="admin-section-card">
                <div className="admin-section-header">
                  <div className="admin-section-title">
                    <ClipboardCheck size={18} style={{ color: '#e11d48' }} />
                    <span>{isMr ? 'सर्व्हे मंजुऱ्या कक्ष (थेट DB)' : 'Survey Approvals (DB)'}</span>
                  </div>
                  <span className="admin-badge-count">{filteredApprovals.length} नोंदी</span>
                </div>

                {/* Filter Pills */}
                <div style={{ display: 'flex', gap: '6px', marginBottom: '12px', overflowX: 'auto', paddingBottom: '4px' }}>
                  {['ALL', 'PENDING_APPROVAL', 'APPROVED', 'REJECTED'].map((st) => (
                    <button
                      key={st}
                      type="button"
                      onClick={() => setApprovalFilter(st)}
                      style={{
                        padding: '4px 10px',
                        borderRadius: '20px',
                        border: 'none',
                        fontSize: '11px',
                        fontWeight: '750',
                        cursor: 'pointer',
                        background: approvalFilter === st ? '#ff5200' : '#f1f5f9',
                        color: approvalFilter === st ? '#ffffff' : '#475569',
                      }}
                    >
                      {st === 'ALL' ? (isMr ? 'सर्व' : 'All') : st === 'PENDING_APPROVAL' ? (isMr ? 'प्रलंबित' : 'Pending') : st === 'APPROVED' ? (isMr ? 'मंजूर' : 'Approved') : (isMr ? 'नाकारलेले' : 'Rejected')}
                    </button>
                  ))}
                </div>

                {filteredApprovals.length === 0 ? (
                  <div style={{ textAlign: 'center', padding: '30px 10px', color: '#64748b' }}>
                    <CheckCircle2 size={36} style={{ color: '#10b981', margin: '0 auto 8px' }} />
                    <div style={{ fontSize: '13px', fontWeight: '750' }}>
                      {isMr ? 'कोणतेही सर्व्हे उपलब्ध नाहीत' : 'No surveys match the filter'}
                    </div>
                  </div>
                ) : (
                  <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
                    {filteredApprovals.map((app) => (
                      <div key={app.id} className="admin-approval-item">
                        <div className="admin-approval-top">
                          <div className="admin-approval-title">{app.citizen_name_mr || app.submission_code}</div>
                          <span style={{ fontSize: '10px', color: '#64748b' }}>
                            {app.created_at ? new Date(app.created_at).toLocaleDateString() : '-'}
                          </span>
                        </div>

                        <div className="admin-approval-loc">
                          <MapPin size={12} />
                          <span>{app.village_mr || 'गाव उपलब्ध नाही'} • {app.booth_no || 'बूथ'}</span>
                        </div>

                        <div style={{ fontSize: '11px', color: '#334155' }}>
                          कोड: <strong>{app.submission_code}</strong> • कार्यकर्ता: <strong>{app.submitted_by_name || '-'}</strong>
                        </div>

                        <div style={{ fontSize: '11px', display: 'flex', alignItems: 'center', gap: '6px' }}>
                          <span>स्थिती:</span>
                          <span
                            style={{
                              fontSize: '10px',
                              fontWeight: '800',
                              padding: '1px 6px',
                              borderRadius: '4px',
                              background: app.status === 'APPROVED' ? '#dcfce7' : app.status === 'REJECTED' ? '#fee2e2' : '#fef3c7',
                              color: app.status === 'APPROVED' ? '#166534' : app.status === 'REJECTED' ? '#991b1b' : '#92400e',
                            }}
                          >
                            {app.status}
                          </span>
                        </div>

                        {app.status === 'PENDING_APPROVAL' && (
                          <div className="admin-approval-buttons">
                            <button
                              type="button"
                              className="admin-btn-approve"
                              onClick={() => handleApprove(app.id)}
                              disabled={actionLoadingId === app.id}
                            >
                              <CheckCircle2 size={14} />
                              <span>{isMr ? 'मंजूर करा' : 'Approve'}</span>
                            </button>
                            <button
                              type="button"
                              className="admin-btn-reject"
                              onClick={() => handleReject(app.id)}
                              disabled={actionLoadingId === app.id}
                            >
                              <XCircle size={14} />
                              <span>{isMr ? 'नाकारा' : 'Reject'}</span>
                            </button>
                          </div>
                        )}
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )}

            {/* ================= TAB 5: CIVIC ISSUES & COMPLAINTS ================= */}
            {activeTab === 'issues' && (
              <div className="admin-section-card">
                <div className="admin-section-header">
                  <div className="admin-section-title">
                    <AlertTriangle size={18} style={{ color: '#ea580c' }} />
                    <span>{isMr ? 'नागरी समस्या व तक्रारी (थेट DB)' : 'Civic Issues (Live DB)'}</span>
                  </div>
                  <span className="admin-badge-count">{filteredIssues.length} तक्रारी</span>
                </div>

                {/* Filter Pills */}
                <div style={{ display: 'flex', gap: '6px', marginBottom: '12px' }}>
                  {['ALL', 'Pending', 'Resolved'].map((st) => (
                    <button
                      key={st}
                      type="button"
                      onClick={() => setIssueFilter(st)}
                      style={{
                        padding: '4px 10px',
                        borderRadius: '20px',
                        border: 'none',
                        fontSize: '11px',
                        fontWeight: '750',
                        cursor: 'pointer',
                        background: issueFilter === st ? '#ff5200' : '#f1f5f9',
                        color: issueFilter === st ? '#ffffff' : '#475569',
                      }}
                    >
                      {st === 'ALL' ? (isMr ? 'सर्व' : 'All') : st === 'Pending' ? (isMr ? 'प्रलंबित' : 'Pending') : (isMr ? 'सोडवलेले' : 'Resolved')}
                    </button>
                  ))}
                </div>

                {filteredIssues.length === 0 ? (
                  <div style={{ textAlign: 'center', padding: '30px 10px', color: '#64748b' }}>
                    <div style={{ fontSize: '13px', fontWeight: '750' }}>
                      {isMr ? 'कोणतीही तक्रार आढळली नाही' : 'No complaints found'}
                    </div>
                  </div>
                ) : (
                  <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                    {filteredIssues.map((issue) => (
                      <div
                        key={issue.id}
                        style={{
                          background: '#f8fafc',
                          border: '1px solid #e2e8f0',
                          borderRadius: '10px',
                          padding: '10px 12px',
                          display: 'flex',
                          flexDirection: 'column',
                          gap: '4px',
                        }}
                      >
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                          <span style={{ fontSize: '12.5px', fontWeight: '800', color: '#0f172a' }}>
                            {issue.category ? `[${issue.category}] ` : ''}{issue.name || issue.complaint_code}
                          </span>
                          <span
                            style={{
                              fontSize: '9.5px',
                              fontWeight: '800',
                              padding: '1px 5px',
                              borderRadius: '4px',
                              background: issue.status === 'Resolved' ? '#dcfce7' : '#fee2e2',
                              color: issue.status === 'Resolved' ? '#166534' : '#991b1b',
                            }}
                          >
                            {issue.status}
                          </span>
                        </div>

                        {issue.desc && (
                          <div style={{ fontSize: '11px', color: '#334155', marginTop: '2px' }}>
                            {issue.desc}
                          </div>
                        )}

                        <div style={{ fontSize: '10.5px', color: '#64748b' }}>
                          तारीख: {issue.date_reported || (issue.created_at ? new Date(issue.created_at).toLocaleDateString() : '-')}
                          {issue.phone && ` • संपर्क: ${issue.phone}`}
                        </div>

                        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginTop: '6px' }}>
                          <span style={{ fontSize: '10px', color: '#94a3b8' }}>
                            कोड: {issue.complaint_code || `#${issue.id}`}
                          </span>

                          {issue.status !== 'Resolved' && (
                            <button
                              type="button"
                              onClick={() => handleUpdateComplaint(issue.id, 'Resolved')}
                              disabled={actionLoadingId === issue.id}
                              style={{
                                background: '#10b981',
                                color: '#fff',
                                border: 'none',
                                borderRadius: '6px',
                                fontSize: '10.5px',
                                padding: '4px 10px',
                                fontWeight: '750',
                                cursor: 'pointer',
                              }}
                            >
                              ✓ {isMr ? 'निवारण पूर्ण (Resolve)' : 'Mark Resolved'}
                            </button>
                          )}
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )}

            {/* ================= TAB 6: MASTER DATA & VILLAGES ================= */}
            {activeTab === 'master_data' && (
              <div className="admin-section-card">
                <div className="admin-section-header">
                  <div className="admin-section-title">
                    <Building size={18} style={{ color: '#2563eb' }} />
                    <span>{isMr ? 'गावनिहाय लोकसंख्या व मतदार (थेट DB)' : 'Village Demographics (DB)'}</span>
                  </div>
                  <span className="admin-badge-count">{filteredVillages.length} गावे</span>
                </div>

                <div style={{ marginBottom: '8px' }}>
                  <input
                    type="text"
                    placeholder={isMr ? 'गाव, तालुका किंवा इनचार्ज शोधा...' : 'Search village, taluka, incharge...'}
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    style={{
                      width: '100%',
                      padding: '8px 12px',
                      borderRadius: '8px',
                      border: '1.5px solid #cbd5e1',
                      fontSize: '12px',
                    }}
                  />
                </div>

                <div style={{ overflowX: 'auto' }}>
                  <table className="admin-village-table">
                    <thead>
                      <tr>
                        <th>{isMr ? 'गाव / परिसर' : 'Village'}</th>
                        <th>{isMr ? 'लोकसंख्या' : 'Population'}</th>
                        <th>{isMr ? 'मतदार' : 'Voters'}</th>
                        <th>{isMr ? 'बूथ' : 'Booths'}</th>
                      </tr>
                    </thead>
                    <tbody>
                      {filteredVillages.map((v, i) => (
                        <tr key={i}>
                          <td>
                            <strong>{v.nameMr}</strong>
                            {v.taluka && (
                              <div style={{ fontSize: '9.5px', color: '#64748b' }}>{v.taluka}</div>
                            )}
                          </td>
                          <td style={{ fontWeight: '750' }}>
                            {v.population ? v.population.toLocaleString('en-IN') : '-'}
                          </td>
                          <td style={{ color: '#2563eb', fontWeight: '800' }}>
                            {v.voters ? v.voters.toLocaleString('en-IN') : '-'}
                          </td>
                          <td>{v.boothsCount}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            )}

            {/* ================= TAB 7: SYSTEM & BACKUP ================= */}
            {activeTab === 'system' && (
              <div className="admin-section-card">
                <div className="admin-section-header">
                  <div className="admin-section-title">
                    <Database size={18} style={{ color: '#7c3aed' }} />
                    <span>{isMr ? 'प्रणाली व डेटाबेस सुरक्षा' : 'System & Security'}</span>
                  </div>
                </div>

                <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
                  <div style={{ background: '#f8fafc', padding: '12px', borderRadius: '10px', border: '1px solid #e2e8f0' }}>
                    <div style={{ fontSize: '13px', fontWeight: '800', color: '#0f172a' }}>
                      डेटाबेस बॅकअप (Instant Database Backup)
                    </div>
                    <div style={{ fontSize: '11px', color: '#64748b', marginTop: '2px' }}>
                      {backups.length > 0 ? (
                        <>शेवटचा बॅकअप: {backups[0].filename} ({backups[0].file_size_formatted || '1.4 MB'})</>
                      ) : (
                        <>थेट MySQL डेटाबेस सुरक्षित बॅकअप</>
                      )}
                    </div>
                    <button
                      type="button"
                      onClick={handleCreateBackup}
                      disabled={actionLoadingId === 'backup'}
                      style={{
                        marginTop: '8px',
                        background: '#10b981',
                        color: '#fff',
                        border: 'none',
                        padding: '8px 14px',
                        borderRadius: '8px',
                        fontSize: '11.5px',
                        fontWeight: '800',
                        cursor: 'pointer',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '6px',
                      }}
                    >
                      {actionLoadingId === 'backup' ? (
                        <Loader2 size={14} className="admin-spin" />
                      ) : (
                        <span>💾</span>
                      )}
                      <span>{isMr ? 'आता डेटाबेस बॅकअप घ्या' : 'Backup Database Now'}</span>
                    </button>
                  </div>

                  {backups.length > 0 && (
                    <div style={{ background: '#f8fafc', padding: '12px', borderRadius: '10px', border: '1px solid #e2e8f0' }}>
                      <div style={{ fontSize: '12.5px', fontWeight: '800', color: '#0f172a', marginBottom: '6px' }}>
                        {isMr ? 'उपलब्ध बॅकअप फाईल्स' : 'Available Backup Files'} ({backups.length})
                      </div>
                      <div style={{ display: 'flex', flexDirection: 'column', gap: '6px' }}>
                        {backups.map((b) => (
                          <div key={b.id} style={{ fontSize: '11px', color: '#334155', display: 'flex', justifyContent: 'space-between', padding: '4px 0', borderBottom: '1px dashed #e2e8f0' }}>
                            <span style={{ fontWeight: '600', maxWidth: '180px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                              📄 {b.filename}
                            </span>
                            <span style={{ color: '#64748b' }}>{b.file_size_formatted || '1.4 MB'}</span>
                          </div>
                        ))}
                      </div>
                    </div>
                  )}

                  <div style={{ background: '#f8fafc', padding: '12px', borderRadius: '10px', border: '1px solid #e2e8f0' }}>
                    <div style={{ fontSize: '13px', fontWeight: '800', color: '#0f172a' }}>
                      सक्रिय सत्र माहिती (Active Session)
                    </div>
                    <div style={{ fontSize: '11px', color: '#64748b', marginTop: '2px' }}>
                      प्रशासक: <code>{user?.email || 'admin@gmail.com'}</code> • अधिकृत टोकन जोडलेले
                    </div>
                  </div>
                </div>
              </div>
            )}
          </>
        )}
      </main>

      {/* ================= FLOATING AI ASSISTANT BUTTON ================= */}
      <button
        type="button"
        className="admin-ai-floating-btn"
        onClick={() => setIsChatbotOpen(true)}
      >
        <Sparkles size={16} />
        <span>{isMr ? '🤖 AI सहाय्यक' : '🤖 AI Assistant'}</span>
      </button>

      {/* ================= AI CHATBOT MODAL BOTTOM SHEET ================= */}
      {isChatbotOpen && (
        <div className="admin-chatbot-modal-overlay" onClick={() => setIsChatbotOpen(false)}>
          <div className="admin-chatbot-modal-sheet" onClick={(e) => e.stopPropagation()}>
            <AdminPhoneChatbot lang={lang} onClose={() => setIsChatbotOpen(false)} />
          </div>
        </div>
      )}

      {/* ================= DOCKED BOTTOM NAVIGATION BAR ================= */}
      <nav className="admin-bottom-nav">
        <button
          type="button"
          className={`admin-bottom-item ${activeTab === 'dashboard' ? 'active' : ''}`}
          onClick={() => setActiveTab('dashboard')}
        >
          <span>📊</span>
          <span className="label">{isMr ? 'डॅशबोर्ड' : 'Dashboard'}</span>
        </button>

        <button
          type="button"
          className={`admin-bottom-item ${activeTab === 'projects' ? 'active' : ''}`}
          onClick={() => setActiveTab('projects')}
        >
          <span>📁</span>
          <span className="label">{isMr ? 'प्रकल्प' : 'Projects'}</span>
        </button>

        <button
          type="button"
          className={`admin-bottom-item ${activeTab === 'attendance' ? 'active' : ''}`}
          onClick={() => setActiveTab('attendance')}
        >
          <span>📍</span>
          <span className="label">{isMr ? 'हजेरी' : 'Attendance'}</span>
        </button>

        <button
          type="button"
          className={`admin-bottom-item ${activeTab === 'approvals' ? 'active' : ''}`}
          onClick={() => setActiveTab('approvals')}
        >
          <span>📝</span>
          {pendingApprovalsCount > 0 && (
            <span className="admin-bottom-badge">{pendingApprovalsCount}</span>
          )}
          <span className="label">{isMr ? 'मंजुरी' : 'Approvals'}</span>
        </button>

        <button
          type="button"
          className="admin-bottom-item"
          onClick={() => setIsDrawerOpen(true)}
        >
          <span>☰</span>
          <span className="label">{isMr ? 'अधिक' : 'More'}</span>
        </button>
      </nav>
    </div>
  );
}
