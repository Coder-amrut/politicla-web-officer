import React, { useState, useEffect, useRef, useMemo, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../../hooks/useAuth';
import BackButton from '../../components/BackButton';
import './worker.css';
import {
  LayoutDashboard,
  User,
  Users,
  ClipboardList,
  MessageSquare,
  Settings,
  Search,
  Bell,
  Menu,
  TrendingUp,
  UserCheck,
  Clock,
  AlertCircle,
  Plus,
  BarChart2,
  FileText,
  Target,
  Send,
  PhoneCall,
  Calendar,
  AlertTriangle,
  ClipboardCheck,
  CalendarCheck,
  PhoneForwarded,
  Edit2,
  MapPin,
  Mail,
  Phone,
  Shield,
  CalendarDays,
  Save,
  X,
  Video,
  MessageCircle,
  MoreVertical,
  MoreHorizontal,
  BookUser,
  Play,
  CheckCheck,
  CheckSquare,
  ArrowLeft,
  Share2,
  Check,
  ArrowRight,
  Lock,
  EyeOff,
  Headphones,
  CheckCircle2,
  Trash2,
  LogIn,
  LogOut,
  Camera,
  RefreshCw,
  Navigation,
  Compass,
  ShieldCheck,
  Wifi,
  WifiOff,
  ChevronRight
} from 'lucide-react';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer
} from 'recharts';
import { translations } from './translations';
import Webcam from 'react-webcam';
import { workerApi } from './workerApi';
import KarkartaEvmVotingSystem from './components/KarkartaEvmVotingSystem';
import {
  isNetworkOnline,
  getOfflineQueue,
  enqueueOfflineItem,
  syncOfflineQueue,
  cacheWorkerData,
  getWorkerCache
} from './offlineSyncManager';
import {
  DashboardTab,
  AttendanceTab,
  ProfileTab,
  ContactsTab,
  VotingTab,
  SurveyTab,
  ComplaintsTab,
  TasksTab,
  HierarchyTab,
} from './tabs';

// Self-contained Live Clock component: ticks every second in its own micro-lifecycle
// preventing the entire 4,000-line WorkerDashboard from re-rendering every 1,000ms.
const LiveClock = React.memo(function LiveClock({ isMr, variant = 'header' }) {
  const [time, setTime] = useState(() => new Date());

  useEffect(() => {
    const timer = setInterval(() => setTime(new Date()), 1000);
    return () => clearInterval(timer);
  }, []);

  const formatted = useMemo(() => {
    try {
      const timeStr = time.toLocaleTimeString(isMr ? 'mr-IN' : 'en-IN', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true,
      });
      const dateStr = time.toLocaleDateString(isMr ? 'mr-IN' : 'en-IN', {
        weekday: 'short',
        day: '2-digit',
        month: 'short',
        year: 'numeric',
      });
      return { time: timeStr, date: dateStr };
    } catch {
      return { time: time.toTimeString().split(' ')[0], date: '' };
    }
  }, [time, isMr]);

  if (variant === 'header') {
    return (
      <div
        className="live-clock-header"
        style={{
          display: 'inline-flex',
          alignItems: 'center',
          gap: '6px',
          padding: '5px 9px',
          borderRadius: '8px',
          background: '#f8fafc',
          border: '1px solid #e2e8f0',
          fontSize: '11.5px',
          color: '#334155',
          fontWeight: '600',
          whiteSpace: 'nowrap',
          flexShrink: 0,
        }}
        title={isMr ? 'भारतीय प्रमाणवेळ (IST)' : 'Indian Standard Time'}
      >
        <span style={{ fontSize: '13px', lineHeight: 1 }}>⏱️</span>
        <span style={{ fontFamily: 'monospace', fontWeight: '800', color: '#0f172a', whiteSpace: 'nowrap' }}>
          {formatted.time}
        </span>
        <span className="live-clock-date" style={{ color: '#64748b', fontSize: '11px', whiteSpace: 'nowrap' }}>
          ({formatted.date})
        </span>
      </div>
    );
  }

  if (variant === 'card') {
    return (
      <div>
        <div style={{ fontSize: '11px', color: '#64748b', fontWeight: '700', textTransform: 'uppercase' }}>
          {formatted.date}
        </div>
        <div style={{ fontSize: '16px', fontWeight: '900', color: '#0f172a', letterSpacing: '0.5px', fontFamily: 'monospace' }}>
          {formatted.time}
        </div>
      </div>
    );
  }

  // inline
  return (
    <span style={{ fontWeight: '800', color: '#0f172a', fontFamily: 'monospace' }}>
      {formatted.time} ({formatted.date})
    </span>
  );
});

export default function WorkerDashboard() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const [voterData, setVoterData] = useState([]);
  const [isSidebarOpen, setSidebarOpen] = useState(false);
  const [activeTab, setActiveTab] = useState('Dashboard');
  const [language, setLanguage] = useState(() => {
    try {
      const stored = localStorage.getItem('karyakarta_lang');
      if (!stored || stored === 'en') {
        localStorage.setItem('karyakarta_lang', 'mr');
        return 'mr';
      }
      return stored;
    } catch {
      return 'mr';
    }
  }); // Default Marathi (mr)
  const changeLanguage = (lang) => {
    setLanguage(lang);
    try {
      localStorage.setItem('karyakarta_lang', lang);
    } catch (e) {
      console.warn('localStorage error:', e);
    }
  };
  const [loading, setLoading] = useState(false);
  const [isCollapsed, setIsCollapsed] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [showFloatingChat, setShowFloatingChat] = useState(false);

  // Offline & Auto-Sync State
  const [isOnline, setIsOnline] = useState(() => isNetworkOnline());
  const [offlineQueueCount, setOfflineQueueCount] = useState(() => getOfflineQueue().length);
  const [isSyncing, setIsSyncing] = useState(false);
  const [syncStatusMsg, setSyncStatusMsg] = useState('');

  const isMr = language === 'mr';

  const t = (key) => translations[language]?.[key] || key;

  // Candidate Voting State
  const [hasVoted, setHasVoted] = useState(false);
  const [votedCandidate, setVotedCandidate] = useState(null);
  const [candidatesList, setCandidatesList] = useState([]);
  const [viewingCandidate, setViewingCandidate] = useState(null);
  const [candidateSearchQuery, setCandidateSearchQuery] = useState('');
  const [selectedPartyFilter, setSelectedPartyFilter] = useState('All');
  const [candidateTab, setCandidateTab] = useState('About');

  // Active Survey Filling Modal State (Only for Allowed Published Forms)
  const [activeSurveyToFill, setActiveSurveyToFill] = useState(null);
  const [surveysList, setSurveysList] = useState([]);
  const [submittingSurvey, setSubmittingSurvey] = useState(false);
  const [surveyStats, setSurveyStats] = useState({
    allocatedForms: 0,
    savedToBackend: 0,
    totalTarget: 0,
    pendingTarget: 0,
  });
  const [savedSubmissionsList, setSavedSubmissionsList] = useState([]);
  const [surveyActiveView, setSurveyActiveView] = useState('forms'); // 'forms' | 'saved_history'
  const [surveyFormData, setSurveyFormData] = useState({
    pramukh_name: '',
    total_matdar: 1,
    phone: '',
    address: '',
    members: [
      {
        id: 'mem_1',
        full_name: '',
        dob: '',
        age: '',
        has_voter_id: 'YES',
        voter_id_no: '',
        relation: 'कुटुंब प्रमुख (Self)',
      },
    ],
    custom_answers: {},
  });

  const navItems = [
    { name: 'Dashboard', key: 'dashboard', icon: LayoutDashboard },
    { name: 'Attendance', key: 'attendance', icon: CalendarCheck },
    { name: 'User Profile', key: 'userProfile', icon: User },
    { name: 'Survey', key: 'survey', icon: ClipboardCheck },
    { name: 'Contact & Meetings', key: 'contactMeetings', icon: Calendar },
    { name: 'Voting', key: 'voting', icon: CheckSquare },
  ];

  const [isEditingProfile, setIsEditingProfile] = useState(false);
  const [profileData, setProfileData] = useState({
    fullName: user?.full_name || '',
    dob: '',
    phone: user?.phone || '',
    email: user?.email || '',
    address: '',
    assignedWard: '',
    primaryBooths: '',
    joinDate: '',
    epic: '',
    responsibility: '',
    employeeCode: '',
  });

  const [contactTab, setContactTab] = useState('contacts');
  const [contactSearchQuery, setContactSearchQuery] = useState('');
  const [contactSentimentFilter, setContactSentimentFilter] = useState('All');
  const [isSchedulingMeeting, setIsSchedulingMeeting] = useState(false);
  const [meetingsList, setMeetingsList] = useState([]);
  const [newMeeting, setNewMeeting] = useState({ title: '', time: '', location: '', attendees: '', type: 'Virtual' });

  const [complaintFilter, setComplaintFilter] = useState('All');
  const [complaintSearchQuery, setComplaintSearchQuery] = useState('');
  const [complaintsData, setComplaintsData] = useState([
    {
      id: 1004,
      complaint_code: 'C-1004',
      name: 'पूजा सुरेश रानडे',
      phone: '99887 45678',
      phone_raw: '9988745678',
      category: 'स्ट्रीट लाईट',
      desc: 'शाळेसमोरील ३ पथदिवे बंद आहेत. विद्युत विभागाकडे लेखी मागणी नोंदवली आहे.',
      date: '०३ ऑक्टोबर २०२६',
      date_reported: '०३ ऑक्टोबर २०२६',
      status: 'Resolved',
    },
    {
      id: 1003,
      complaint_code: 'C-1003',
      name: 'योगेश दीपक कदम',
      phone: '97641 12233',
      phone_raw: '9764112233',
      category: 'कचरा व्यवस्थापन',
      desc: 'गावभाग परिसरात घंटागाडी वेळेवर येत नाही. आरोग्य निरीक्षकांशी चर्चा करून नवीन वेळ निश्चित केली आहे.',
      date: '०५ ऑक्टोबर २०२६',
      date_reported: '०५ ऑक्टोबर २०२६',
      status: 'In Progress',
    },
    {
      id: 1002,
      complaint_code: 'C-1002',
      name: 'रेखा पांडुरंग सावंत',
      phone: '94215 56677',
      phone_raw: '9421556677',
      category: 'रस्ता डांबरीकरण',
      desc: 'वसंत कॉलनी मुख्य रस्त्यावरील खड्डे बुजविणे आवश्यक. सस्तन आवरीपाने आवडत. महानगरपालिकेकडून डांबरीकरणाचे काम पूर्ण झाले.',
      date: '०७ ऑक्टोबर २०२६',
      date_reported: '०७ ऑक्टोबर २०२६',
      status: 'Resolved',
    },
    {
      id: 1001,
      complaint_code: 'C-1001',
      name: 'अमोल बापूसाहेब पाटील',
      phone: '98220 11445',
      phone_raw: '9822011445',
      category: 'पाणी पुरवठा',
      desc: 'प्रभाग क्र. १२ मधील जलवाहिनी फुटल्याने कमी दाबाने पाणी येत आहे. दुरुस्तीचे काम हाती घेतले आहे.',
      date: '०१ ऑक्टोबर २०२६',
      date_reported: '०१ ऑक्टोबर २०२६',
      status: 'Pending',
    },
  ]);

  const [attendanceState, setAttendanceState] = useState('Not Started');
  const [attendanceHistory, setAttendanceHistory] = useState([]);

  const [votersList, setVotersList] = useState([]);
  const [tasksList, setTasksList] = useState([]);
  const [followUpsList, setFollowUpsList] = useState([]);

  // Citizen Grievances / Complaints State
  const [isAddingComplaint, setIsAddingComplaint] = useState(false);
  const [submittingComplaint, setSubmittingComplaint] = useState(false);
  const [complaintDuplicateWarning, setComplaintDuplicateWarning] = useState(null);
  const [complaintFormData, setComplaintFormData] = useState({
    name: '',
    phone: '',
    category: 'पाणी पुरवठा (Water Supply)',
    desc: '',
    urgency: 'मध्यम (Medium)',
    photo: null,
    location: '',
  });

  // Tasks & Follow-ups State
  const [taskActiveTab, setTaskActiveTab] = useState('tasks'); // 'tasks' | 'followups'
  const [isAddingTask, setIsAddingTask] = useState(false);
  const [newTaskData, setNewTaskData] = useState({
    title: '',
    deadline: '',
    priority: 'Medium',
    category: 'बूथ प्रचार (Booth Outreach)',
  });
  const [isAddingFollowUp, setIsAddingFollowUp] = useState(false);
  const [newFollowUpData, setNewFollowUpData] = useState({
    citizen_name: '',
    phone: '',
    topic: '',
    next_date: '',
    notes: '',
  });

  // 6-Level Geographic Hierarchy State
  const [hierarchyData, setHierarchyData] = useState(null);
  const [loadingHierarchy, setLoadingHierarchy] = useState(false);
  const [selectedHierarchyLevel, setSelectedHierarchyLevel] = useState(6);

  // Visit Log State
  const [activeVisitContact, setActiveVisitContact] = useState(null);
  const [visitFormData, setVisitFormData] = useState({
    name: '',
    phone: '',
    status: 'Supportive',
    notes: '',
    location: '',
  });

  // Inline Survey Duplicate Warning State
  const [surveyDuplicateWarning, setSurveyDuplicateWarning] = useState(null);

  const [dashboardStats, setDashboardStats] = useState({
    totalAssigned: 0,
    contacted: 0,
    supportive: 0,
    pendingFollowUps: 0,
    surveysCompleted: 0,
    surveysTarget: 0,
  });

  const [activities, setActivities] = useState([]);

  // Data Fetching Functions
  const loadOverview = async () => {
    try {
      const data = await workerApi.getOverview();
      if (data) {
        if (data.stats) setDashboardStats(data.stats);
        if (data.voterData) setVoterData(data.voterData);
        if (data.activities) setActivities(data.activities);
        cacheWorkerData('overview', data);
      }
    } catch (e) {
      console.warn('Overview load issue:', e);
      const cached = getWorkerCache('overview');
      if (cached) {
        if (cached.stats) setDashboardStats(cached.stats);
        if (cached.voterData) setVoterData(cached.voterData);
        if (cached.activities) setActivities(cached.activities);
      }
    }
  };

  const loadProfile = async () => {
    try {
      const data = await workerApi.getProfile();
      if (data) {
        setProfileData(data);
      }
    } catch (e) {
      console.warn('Profile load issue:', e);
    }
  };

  const loadTasks = async () => {
    try {
      const data = await workerApi.getTasks();
      if (Array.isArray(data)) setTasksList(data);
    } catch (e) {
      console.warn('Tasks load issue:', e);
    }
  };

  const loadFollowUps = async () => {
    try {
      const data = await workerApi.getFollowUps();
      if (Array.isArray(data)) setFollowUpsList(data);
    } catch (e) {
      console.warn('Follow-ups load issue:', e);
    }
  };

  const loadSurveys = async () => {
    try {
      const res = await workerApi.getSurveys();
      const list = Array.isArray(res) ? res : (res?.data || []);
      setSurveysList(list);

      const allocatedForms = res?.allocated_forms_count ?? list.length;
      const savedToBackend = res?.total_saved_to_backend ?? list.reduce((acc, s) => acc + (s.completed_voters || 0), 0);
      const totalTarget = res?.total_allocated_target ?? list.reduce((acc, s) => acc + (s.allocated_target || s.total_voters || 50), 0);
      const pendingTarget = res?.pending_target ?? Math.max(0, totalTarget - savedToBackend);

      setSurveyStats({
        allocatedForms,
        savedToBackend,
        totalTarget,
        pendingTarget,
      });

      if (Array.isArray(res?.recent_submissions)) {
        setSavedSubmissionsList(res.recent_submissions);
      }
      cacheWorkerData('surveys', { list, stats: { allocatedForms, savedToBackend, totalTarget, pendingTarget }, submissions: res?.recent_submissions || [] });
    } catch (e) {
      console.warn('Surveys load issue:', e);
      const cached = getWorkerCache('surveys');
      if (cached) {
        if (Array.isArray(cached)) {
          setSurveysList(cached);
        } else if (cached.list) {
          setSurveysList(cached.list || []);
          if (cached.stats) setSurveyStats(cached.stats);
          if (cached.submissions) setSavedSubmissionsList(cached.submissions);
        }
      } else {
        setSurveysList([]);
      }
    }
  };

  const loadComplaints = async () => {
    try {
      const data = await workerApi.getComplaints();
      if (Array.isArray(data) && data.length > 0) setComplaintsData(data);
    } catch (e) {
      console.warn('Complaints load issue:', e);
    }
  };

  const [attendancePolicy, setAttendancePolicy] = useState(null);

  const loadAttendance = async () => {
    try {
      const data = await workerApi.getAttendance();
      if (data) {
        if (data.currentState) setAttendanceState(data.currentState);
        if (Array.isArray(data.history)) setAttendanceHistory(data.history);
        cacheWorkerData('attendance', data);
      }
    } catch (e) {
      console.warn('Attendance load issue:', e);
      const cached = getWorkerCache('attendance');
      if (cached) {
        if (cached.currentState) setAttendanceState(cached.currentState);
        if (Array.isArray(cached.history)) setAttendanceHistory(cached.history);
      }
    }
  };

  const loadAttendancePolicy = async () => {
    try {
      const data = await workerApi.getAttendancePolicy();
      if (data) setAttendancePolicy(data);
    } catch (e) {
      console.warn('Attendance policy load issue:', e);
    }
  };

  const loadContacts = async () => {
    try {
      const data = await workerApi.getContacts();
      if (Array.isArray(data)) {
        setVotersList(data);
        cacheWorkerData('contacts', data);
      }
    } catch (e) {
      console.warn('Contacts load issue:', e);
      const cached = getWorkerCache('contacts');
      if (cached && Array.isArray(cached)) setVotersList(cached);
    }
  };

  const loadMeetings = async () => {
    try {
      const data = await workerApi.getMeetings();
      if (Array.isArray(data)) {
        setMeetingsList(data);
        cacheWorkerData('meetings', data);
      }
    } catch (e) {
      console.warn('Meetings load issue:', e);
      const cached = getWorkerCache('meetings');
      if (cached && Array.isArray(cached)) setMeetingsList(cached);
    }
  };

  const loadCandidates = async () => {
    try {
      const data = await workerApi.getCandidates();
      if (data) {
        if (Array.isArray(data.candidates)) setCandidatesList(data.candidates);
        if (data.hasVoted !== undefined) setHasVoted(data.hasVoted);
        if (data.votedCandidate) setVotedCandidate(data.votedCandidate);
      }
    } catch (e) {
      console.warn('Candidates load issue:', e);
    }
  };

  const loadHierarchy = async () => {
    try {
      setLoadingHierarchy(true);
      const data = await workerApi.getGeographyHierarchy();
      if (data) setHierarchyData(data);
    } catch (e) {
      console.warn('Hierarchy load issue:', e);
    } finally {
      setLoadingHierarchy(false);
    }
  };

  // Check Duplicate helper
  const checkDuplicateField = async (field, val, setWarning) => {
    if (!val || val.trim().length < 6) {
      setWarning(null);
      return;
    }
    try {
      const res = await workerApi.checkDuplicate(field === 'phone' ? { phone: val } : { voter_id: val });
      if (res?.is_duplicate && res?.matches?.length > 0) {
        setWarning(res.matches[0]);
      } else {
        setWarning(null);
      }
    } catch (e) {
      console.warn('Duplicate check error:', e);
    }
  };

  // Complaint Submit Handler
  const handleComplaintSubmit = async (e) => {
    e.preventDefault();
    if (!complaintFormData.name.trim() || !complaintFormData.desc.trim()) {
      alert(isMr ? 'कृपया नागरिकाचे नाव आणि समस्येचे वर्णन भरा.' : 'Please enter citizen name and description.');
      return;
    }

    const payload = {
      ...complaintFormData,
      location: complaintFormData.location || currentCoords?.address || '',
    };

    if (!isNetworkOnline()) {
      enqueueOfflineItem({
        type: 'COMPLAINT',
        label_mr: `नागरिक तक्रार - ${complaintFormData.name} (${complaintFormData.category})`,
        label_en: `Citizen Complaint - ${complaintFormData.name}`,
        payload,
      });

      alert(isMr
        ? '📶 इंटरनेट जोडणी नाही.\nतक्रार डिव्हाइसवर सुरक्षित साठवली आहे!\nनेटवर्क येताच ती आपोआप सर्व्हरवर सिंक होईल.'
        : '📶 Offline.\nComplaint saved locally on device!\nIt will automatically sync when online.');

      setIsAddingComplaint(false);
      setComplaintFormData({
        name: '',
        phone: '',
        category: 'पाणी पुरवठा (Water Supply)',
        desc: '',
        urgency: 'मध्यम (Medium)',
        photo: null,
        location: '',
      });
      setComplaintDuplicateWarning(null);
      return;
    }

    setSubmittingComplaint(true);
    try {
      await workerApi.storeComplaint(payload);
      alert(isMr ? '✓ तक्रार / समस्या यशस्वीरीत्या नोंदवली गेली!' : '✓ Citizen complaint registered successfully!');
      setIsAddingComplaint(false);
      setComplaintFormData({
        name: '',
        phone: '',
        category: 'पाणी पुरवठा (Water Supply)',
        desc: '',
        urgency: 'मध्यम (Medium)',
        photo: null,
        location: '',
      });
      setComplaintDuplicateWarning(null);
      loadComplaints();
      loadOverview();
    } catch (err) {
      console.error('Failed to submit complaint:', err);
      alert(isMr ? 'तक्रार नोंदवताना त्रुटी आली.' : 'Failed to register complaint.');
    } finally {
      setSubmittingComplaint(false);
    }
  };

  // Task Toggle & Create
  const handleToggleTask = async (id) => {
    try {
      await workerApi.toggleTask(id);
      loadTasks();
      loadOverview();
    } catch (e) {
      console.error('Failed to toggle task:', e);
    }
  };

  const handleCreateTask = async (e) => {
    e.preventDefault();
    if (!newTaskData.title.trim()) return;
    try {
      await workerApi.createTask(newTaskData);
      setIsAddingTask(false);
      setNewTaskData({ title: '', deadline: '', priority: 'Medium', category: 'बूथ प्रचार (Booth Outreach)' });
      loadTasks();
      loadOverview();
    } catch (e) {
      console.error('Failed to create task:', e);
    }
  };

  // Follow-up Update & Create
  const handleUpdateFollowUpStatus = async (id, status) => {
    try {
      await workerApi.updateFollowUpStatus(id, status);
      loadFollowUps();
      loadOverview();
    } catch (e) {
      console.error('Failed to update follow-up:', e);
    }
  };

  const handleCreateFollowUp = async (e) => {
    e.preventDefault();
    if (!newFollowUpData.citizen_name.trim()) return;
    try {
      await workerApi.createFollowUp(newFollowUpData);
      setIsAddingFollowUp(false);
      setNewFollowUpData({ citizen_name: '', phone: '', topic: '', next_date: '', notes: '' });
      loadFollowUps();
      loadOverview();
    } catch (e) {
      console.error('Failed to create follow-up:', e);
    }
  };

  // Visit Log Handler
  const handleSaveVisitLog = async (e) => {
    e.preventDefault();
    if (!visitFormData.name.trim()) return;
    try {
      await workerApi.recordVisitLog({
        ...visitFormData,
        location: visitFormData.location || currentCoords?.address || '',
      });
      alert(isMr ? '✓ भेट यशस्वीरीत्या नोंदवली गेली!' : '✓ Visit log recorded successfully!');
      setActiveVisitContact(null);
      loadContacts();
      loadOverview();
    } catch (e) {
      console.error('Failed to record visit log:', e);
    }
  };

  // Sync and fetch all data on mount or when user changes
  useEffect(() => {
    setLoading(true);
    Promise.all([
      loadOverview(),
      loadProfile(),
      loadTasks(),
      loadFollowUps(),
      loadSurveys(),
      loadComplaints(),
      loadAttendance(),
      loadAttendancePolicy(),
      loadContacts(),
      loadMeetings(),
      loadCandidates(),
      loadHierarchy(),
    ]).finally(() => setLoading(false));
  }, [user]);

  // Handle manual or automatic queue sync
  const handleSyncQueue = useCallback(async (isAuto = false) => {
    if (!isNetworkOnline()) {
      if (!isAuto) {
        alert(isMr ? '📶 इंटरनेट जोडणी उपलब्ध नाही. कृपया नेटवर्क उपलब्ध झाल्यावर प्रयत्न करा.' : '📶 No internet connection. Please try again when online.');
      }
      return;
    }

    const currentQueue = getOfflineQueue();
    if (currentQueue.length === 0) {
      if (!isAuto) {
        alert(isMr ? 'सर्व माहिती आधीच डेटाबेसमध्ये अद्ययावत आहे!' : 'All offline data is already synced!');
      }
      return;
    }

    setIsSyncing(true);
    setSyncStatusMsg(isMr ? 'माहिती सिंक होत आहे...' : 'Syncing data...');
    try {
      const result = await syncOfflineQueue(workerApi);
      if (result.synced > 0) {
        const msg = isMr
          ? `✓ ${result.synced} ऑफलाइन नोंदी डेटाबेसमध्ये यशस्वीरीत्या सिंक झाल्या!`
          : `✓ ${result.synced} offline records successfully synced to database!`;
        setSyncStatusMsg(msg);
        alert(msg);
        // Refresh all dashboard live data
        Promise.all([
          loadOverview(),
          loadAttendance(),
          loadSurveys(),
          loadMeetings(),
          loadComplaints()
        ]);
      }
    } catch (e) {
      console.error('Queue sync error:', e);
    } finally {
      setIsSyncing(false);
      setOfflineQueueCount(getOfflineQueue().length);
      setTimeout(() => setSyncStatusMsg(''), 5000);
    }
  }, [isMr]);

  // Network listener & auto-sync trigger
  useEffect(() => {
    const handleOnline = () => {
      setIsOnline(true);
      // Automatically sync pending items when network reconnects
      handleSyncQueue(true);
    };

    const handleOffline = () => {
      setIsOnline(false);
    };

    const handleQueueChange = (e) => {
      setOfflineQueueCount(e.detail?.count ?? getOfflineQueue().length);
    };

    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);
    window.addEventListener('karyakarta:offline-queue-changed', handleQueueChange);

    // Initial check: if online and pending items exist, trigger auto-sync
    if (isNetworkOnline() && getOfflineQueue().length > 0) {
      handleSyncQueue(true);
    }

    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
      window.removeEventListener('karyakarta:offline-queue-changed', handleQueueChange);
    };
  }, [handleSyncQueue]);

  const pendingFollowUps = complaintsData.filter((c) => c.status === 'Pending' || c.status === 'In Progress').length + meetingsList.length;

  const badgeConfig = {
    live: { bg: '#ecfdf5', border: '#a7f3d0', text: '#059669' },
    blue: { bg: '#eff6ff', border: '#bfdbfe', text: '#1d4ed8' },
    amber: { bg: '#fffbeb', border: '#fde68a', text: '#b45309' },
    purple: { bg: '#faf5ff', border: '#e9d5ff', text: '#7e22ce' },
    red: { bg: '#fef2f2', border: '#fecaca', text: '#b91c1c' },
    indigo: { bg: '#eef2ff', border: '#c7d2fe', text: '#4338ca' },
    teal: { bg: '#f0fdfa', border: '#99f6e4', text: '#0f766e' },
  };

  const addActivity = (type, title, bgColor, color) => {
    setActivities((prev) => [
      { id: Date.now(), type, bgColor, color, title, time: 'आत्ताच (Just now)' },
      ...prev.slice(0, 5),
    ]);
  };

  // Webcam Attendance & Dedicated Punch In / Punch Out
  const [isScanningFace, setIsScanningFace] = useState(false);
  const [punchMode, setPunchMode] = useState('IN'); // 'IN' or 'OUT'
  const [webcamError, setWebcamError] = useState(false);
  const [locationDetecting, setLocationDetecting] = useState(false);
  const [currentCoords, setCurrentCoords] = useState(null);
  const webcamRef = useRef(null);

  const startPunchIn = () => {
    setPunchMode('IN');
    setIsScanningFace(true);
    setWebcamError(false);
    detectCurrentLocation();
  };

  const startPunchOut = () => {
    setPunchMode('OUT');
    setIsScanningFace(true);
    setWebcamError(false);
    detectCurrentLocation();
  };

  const detectCurrentLocation = () => {
    if (!navigator.geolocation) return;
    setLocationDetecting(true);
    navigator.geolocation.getCurrentPosition(
      async (pos) => {
        const lat = Number(pos.coords.latitude.toFixed(6));
        const lng = Number(pos.coords.longitude.toFixed(6));
        let addr = `अक्षांश: ${lat}, रेखांश: ${lng}`;
        try {
          const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18`);
          if (res.ok) {
            const d = await res.json();
            if (d.display_name) addr = d.display_name;
          }
        } catch (e) { }
        setCurrentCoords({ latitude: lat, longitude: lng, address: addr });
        setLocationDetecting(false);
      },
      () => setLocationDetecting(false),
      { timeout: 7000, enableHighAccuracy: true }
    );
  };

  const confirmPunch = async () => {
    let photoSrc = null;
    try {
      if (webcamRef.current) {
        photoSrc = webcamRef.current.getScreenshot();
      }
    } catch (e) {
      console.warn('Webcam screenshot capture issue:', e);
    }

    setIsScanningFace(false);

    // Retrieve current geolocation if not already cached
    const getLocationData = () =>
      new Promise((resolve) => {
        if (currentCoords) {
          resolve(currentCoords);
          return;
        }
        if (!navigator.geolocation) {
          resolve(null);
          return;
        }
        navigator.geolocation.getCurrentPosition(
          async (pos) => {
            const lat = Number(pos.coords.latitude.toFixed(6));
            const lng = Number(pos.coords.longitude.toFixed(6));
            let addr = `अक्षांश: ${lat}, रेखांश: ${lng}`;
            try {
              const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18`);
              if (res.ok) {
                const d = await res.json();
                if (d.display_name) addr = d.display_name;
              }
            } catch (e) { }
            resolve({ latitude: lat, longitude: lng, address: addr });
          },
          () => resolve(null),
          { timeout: 7000, enableHighAccuracy: true }
        );
      });

    const locationData = (await getLocationData()) || {};

    // 1. Direct offline check
    if (!isNetworkOnline()) {
      enqueueOfflineItem({
        type: punchMode === 'IN' ? 'ATTENDANCE_IN' : 'ATTENDANCE_OUT',
        label_mr: punchMode === 'IN' ? 'सकाळची उपस्थिती (Punch In)' : 'कर्तव्य समाप्ती (Punch Out)',
        label_en: punchMode === 'IN' ? 'Punch In Attendance' : 'Punch Out Attendance',
        payload: {
          photo: photoSrc,
          locationData
        }
      });

      if (punchMode === 'IN') {
        setAttendanceState('Punched In');
        addActivity('clock', 'सकाळची उपस्थिती (Offline Punch In) नोंदवली', '#ecfdf5', '#059669');
        alert(isMr
          ? '📶 ऑफलाइन हजेरी (Punch In) डिव्हाइसवर नोंदवली गेली आहे!\nनेटवर्क उपलब्ध होताच ती आपोआप डेटाबेसमध्ये सिंक होईल.'
          : '📶 Punched In offline! Will automatically sync to database when online.');
      } else {
        setAttendanceState('Completed');
        addActivity('clock', 'कर्तव्य समाप्ती (Offline Punch Out) नोंदवली', '#fff7ed', '#ea580c');
        alert(isMr
          ? '📶 ऑफलाइन निर्गमन (Punch Out) डिव्हाइसवर नोंदवले गेले आहे!\nनेटवर्क उपलब्ध होताच ते आपोआप डेटाबेसमध्ये सिंक होईल.'
          : '📶 Punched Out offline! Will automatically sync to database when online.');
      }
      return;
    }

    try {
      if (punchMode === 'IN') {
        await workerApi.punchIn(photoSrc, locationData);
        setAttendanceState('Punched In');
        addActivity('clock', 'सकाळची उपस्थिती (Punch In) नोंदवली', '#ecfdf5', '#059669');
        alert(isMr ? 'हजेरी (Punch In) यशस्वीरीत्या नोंदवली!' : 'Punched In successfully!');
      } else {
        await workerApi.punchOut(photoSrc, locationData);
        setAttendanceState('Completed');
        addActivity('clock', 'कर्तव्य समाप्ती (Punch Out) नोंदवली', '#fff7ed', '#ea580c');
        alert(isMr ? 'निर्गमन (Punch Out) यशस्वीरीत्या नोंदवले!' : 'Punched Out successfully!');
      }
      await Promise.all([
        loadAttendance(),
        loadOverview(),
        loadAttendancePolicy()
      ]);
    } catch (err) {
      console.error('Attendance error:', err);
      // Fallback to offline queue on network failures
      if (!isNetworkOnline() || err?.code === 'ERR_NETWORK' || !err?.response) {
        enqueueOfflineItem({
          type: punchMode === 'IN' ? 'ATTENDANCE_IN' : 'ATTENDANCE_OUT',
          label_mr: punchMode === 'IN' ? 'सकाळची उपस्थिती (Punch In)' : 'कर्तव्य समाप्ती (Punch Out)',
          label_en: punchMode === 'IN' ? 'Punch In Attendance' : 'Punch Out Attendance',
          payload: { photo: photoSrc, locationData }
        });
        if (punchMode === 'IN') setAttendanceState('Punched In');
        else setAttendanceState('Completed');
        alert(isMr
          ? '📶 नेटवर्क उपलब्ध नसल्यामुळे हजेरी ऑफलाइन साठवली आहे. इंटरनेट आल्यावर आपोआप सिंक होईल.'
          : '📶 Network unavailable. Attendance saved offline and will auto-sync when online.');
        return;
      }
      const msg = err.response?.data?.message || err.message || 'उपस्थिती नोंदवताना त्रुटी आली.';
      alert(msg);
    }
  };

  const handleUpdateComplaintStatus = async (id, newStatus) => {
    try {
      await workerApi.updateComplaintStatus(id, newStatus);
      setComplaintsData((prev) => prev.map((c) => (c.id === id || c.complaint_code === id ? { ...c, status: newStatus } : c)));
      loadOverview();
    } catch (err) {
      console.error('Complaint status update error:', err);
    }
  };

  const handleScheduleMeeting = async () => {
    if (newMeeting.title) {
      if (!isNetworkOnline()) {
        enqueueOfflineItem({
          type: 'MEETING',
          label_mr: `बैठक - ${newMeeting.title}`,
          label_en: `Meeting - ${newMeeting.title}`,
          payload: newMeeting,
        });
        setMeetingsList(prev => [{ ...newMeeting, id: `offline_${Date.now()}`, time: newMeeting.time || 'आज' }, ...prev]);
        setIsSchedulingMeeting(false);
        setNewMeeting({ title: '', time: '', location: '', attendees: '', type: 'Virtual' });
        alert(isMr ? '📶 बैठक ऑफलाइन साठवली! इंटरनेट उपलब्ध झाल्यावर आपोआप डेटाबेसमध्ये जोडली जाईल.' : '📶 Meeting saved offline! Will auto-sync when online.');
        return;
      }

      try {
        await workerApi.scheduleMeeting(newMeeting);
        setIsSchedulingMeeting(false);
        setNewMeeting({ title: '', time: '', location: '', attendees: '', type: 'Virtual' });
        loadMeetings();
        loadOverview();
      } catch (err) {
        console.error('Meeting schedule error:', err);
        if (!isNetworkOnline() || err?.code === 'ERR_NETWORK' || !err?.response) {
          enqueueOfflineItem({
            type: 'MEETING',
            label_mr: `बैठक - ${newMeeting.title}`,
            label_en: `Meeting - ${newMeeting.title}`,
            payload: newMeeting,
          });
          setMeetingsList(prev => [{ ...newMeeting, id: `offline_${Date.now()}`, time: newMeeting.time || 'आज' }, ...prev]);
          setIsSchedulingMeeting(false);
          setNewMeeting({ title: '', time: '', location: '', attendees: '', type: 'Virtual' });
          alert(isMr ? '📶 नेटवर्क खंडित झाले. बैठक ऑफलाइन साठवली आहे.' : '📶 Network error. Meeting saved offline.');
        }
      }
    }
  };

  const handleProfileChange = (e) => {
    const { name, value } = e.target;
    setProfileData((prev) => ({
      ...prev,
      [name]: value,
    }));
  };

  const saveProfile = async () => {
    try {
      await workerApi.updateProfile(profileData);
      setIsEditingProfile(false);
      loadProfile();
      loadOverview();
      alert(language === 'mr' ? 'अधिकारी माहिती यशस्वीरीत्या जतन झाली!' : 'Profile updated successfully!');
    } catch (err) {
      console.error('Save profile error:', err);
      alert('माहिती जतन करताना त्रुटी आली.');
    }
  };

  const handleLogout = async () => {
    await logout();
    navigate('/login', { replace: true });
  };

  // Toggle Task Completion in Backend
  const toggleTask = async (taskId) => {
    try {
      const res = await workerApi.toggleTask(taskId);
      setTasksList((prev) => prev.map((t) => (t.id === taskId ? { ...t, completed: res.completed } : t)));
      loadOverview();
    } catch (err) {
      console.error('Task toggle error:', err);
    }
  };

  // Follow-up Completion in Backend
  const handleFollowUpDone = async (id, name) => {
    try {
      await workerApi.updateFollowUpStatus(id, 'Completed');
      setFollowUpsList((prev) => prev.map((item) => (item.id === id ? { ...item, status: 'Completed' } : item)));
      addActivity('clipboard', `${name} यांचा पाठपुरावा पूर्ण केला`, '#DCFCE7', 'var(--success)');
      loadOverview();
    } catch (err) {
      console.error('Follow-up error:', err);
    }
  };

  // Helpers for Family Members in Published Survey
  const calculateAge = (dobString) => {
    if (!dobString) return '';
    const bDate = new Date(dobString);
    if (isNaN(bDate.getTime())) return '';
    const today = new Date();
    let age = today.getFullYear() - bDate.getFullYear();
    const m = today.getMonth() - bDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < bDate.getDate())) age--;
    return age >= 0 ? age : '';
  };

  const handleWorkerMemberChange = (id, field, value) => {
    setSurveyFormData((prev) => {
      const updatedMembers = prev.members.map((m) => {
        if (m.id !== id) return m;
        const updated = { ...m, [field]: value };
        if (field === 'dob') {
          const autoAge = calculateAge(value);
          if (autoAge !== '') updated.age = autoAge;
        }
        return updated;
      });
      return {
        ...prev,
        members: updatedMembers,
      };
    });
  };

  const handleTotalMatdarChange = (count) => {
    if (count === '' || count === undefined || count === null) {
      setSurveyFormData((prev) => ({
        ...prev,
        total_matdar: '',
      }));
      return;
    }
    const rawVal = parseInt(count, 10);
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
  };

  const handleWorkerAddMember = () => {
    setSurveyFormData((prev) => {
      const nextMembers = [
        ...(prev.members || []),
        {
          id: `mem_${Date.now()}`,
          full_name: '',
          dob: '',
          age: '',
          has_voter_id: 'YES',
          voter_id_no: '',
          relation: (prev.members || []).length === 0 ? 'कुटुंब प्रमुख (Self)' : 'इतर (Other)',
        },
      ];
      return {
        ...prev,
        members: nextMembers,
        total_matdar: nextMembers.length,
      };
    });
  };

  const handleWorkerRemoveMember = (id) => {
    setSurveyFormData((prev) => {
      const nextMembers = (prev.members || []).filter((m) => m.id !== id);
      return {
        ...prev,
        members: nextMembers,
        total_matdar: nextMembers.length,
      };
    });
  };

  // Open survey filling modal and prepare default and custom answers
  const handleOpenSurveyModal = (survey) => {
    setActiveSurveyToFill(survey);
    const initialAnswers = {};
    if (Array.isArray(survey.custom_questions)) {
      survey.custom_questions.forEach((q) => {
        if (q.type === 'multi_choice') {
          initialAnswers[q.id] = [];
        } else if (q.type === 'rating') {
          initialAnswers[q.id] = 5;
        } else {
          initialAnswers[q.id] = '';
        }
      });
    }
    setSurveyFormData({
      pramukh_name: '',
      total_matdar: 1,
      phone: '',
      address: '',
      members: [
        {
          id: 'mem_1',
          full_name: '',
          dob: '',
          age: '',
          has_voter_id: 'YES',
          voter_id_no: '',
          relation: 'कुटुंब प्रमुख (Self)',
        },
      ],
      custom_answers: initialAnswers,
    });
  };

  const handleCustomAnswerChange = (questionId, value) => {
    setSurveyFormData((prev) => ({
      ...prev,
      custom_answers: {
        ...prev.custom_answers,
        [questionId]: value,
      },
    }));
  };

  const handleMultiChoiceToggle = (questionId, option) => {
    setSurveyFormData((prev) => {
      const currentList = Array.isArray(prev.custom_answers?.[questionId]) ? prev.custom_answers[questionId] : [];
      const nextList = currentList.includes(option)
        ? currentList.filter((item) => item !== option)
        : [...currentList, option];
      return {
        ...prev,
        custom_answers: {
          ...prev.custom_answers,
          [questionId]: nextList,
        },
      };
    });
  };

  // Submit filled published survey form to backend
  const handleSurveySubmit = async (e) => {
    e.preventDefault();
    if (!surveyFormData.pramukh_name.trim()) {
      alert(language === 'mr' ? 'कृपया कुटुंब प्रमुखाचे नाव भरा.' : 'Please enter Family Head Name.');
      return;
    }

    // Ensure first member has the family head name if left blank
    const sanitizedMembers = [...surveyFormData.members];
    if (sanitizedMembers.length > 0 && !sanitizedMembers[0].full_name?.trim()) {
      sanitizedMembers[0].full_name = surveyFormData.pramukh_name;
    }

    // Ensure all members have their full_name filled (if any members are registered)
    if (sanitizedMembers.length > 0) {
      const emptyMemberIndex = sanitizedMembers.findIndex((m) => !m.full_name?.trim());
      if (emptyMemberIndex !== -1) {
        alert(
          language === 'mr'
            ? `कृपया सर्व कुटुंब सदस्यांची माहिती भरा (सदस्य क्र. ${emptyMemberIndex + 1} चे नाव बाकी आहे). एकूण ${sanitizedMembers.length} सदस्यांचे फॉर्म भरणे आवश्यक आहे.`
            : `Please fill in all member details (Member #${emptyMemberIndex + 1} name is empty). Details for all ${sanitizedMembers.length} members are required.`
        );
        return;
      }
    }

    // 1. Direct offline check
    if (!isNetworkOnline()) {
      enqueueOfflineItem({
        type: 'SURVEY',
        label_mr: `मतदार सर्व्हे - ${surveyFormData.pramukh_name}`,
        label_en: `Survey - ${surveyFormData.pramukh_name}`,
        payload: {
          published_form_id: activeSurveyToFill?.id,
          surveyId: activeSurveyToFill?.id,
          pramukh_name: surveyFormData.pramukh_name,
          citizenName: surveyFormData.pramukh_name,
          total_matdar: surveyFormData.total_matdar,
          phone: surveyFormData.phone,
          address: surveyFormData.address,
          members: sanitizedMembers,
          custom_answers: surveyFormData.custom_answers,
        }
      });

      alert(language === 'mr'
        ? '📶 इंटरनेट जोडणी उपलब्ध नाही.\nसर्व्हे फॉर्म या डिव्हाइसवर स्थानिक पातळीवर सुरक्षित साठवला आहे!\nइंटरनेट सुरू होताच तो आपोआप डेटाबेसमध्ये सिंक होईल.'
        : '📶 No internet connection.\nSurvey form saved locally on device!\nIt will automatically sync to database when online.');

      setActiveSurveyToFill(null);
      setSurveyFormData({
        pramukh_name: '',
        total_matdar: 1,
        phone: '',
        address: '',
        members: [
          {
            id: 'mem_1',
            full_name: '',
            dob: '',
            age: '',
            has_voter_id: 'YES',
            voter_id_no: '',
            relation: 'कुटुंब प्रमुख (Self)',
          },
        ],
        custom_answers: {},
      });
      return;
    }

    setSubmittingSurvey(true);
    try {
      await workerApi.submitSurvey({
        published_form_id: activeSurveyToFill?.id,
        surveyId: activeSurveyToFill?.id,
        pramukh_name: surveyFormData.pramukh_name,
        citizenName: surveyFormData.pramukh_name,
        total_matdar: surveyFormData.total_matdar,
        phone: surveyFormData.phone,
        address: surveyFormData.address,
        members: sanitizedMembers,
        custom_answers: surveyFormData.custom_answers,
      });

      alert(language === 'mr' ? '✓ मतदार सर्वेक्षण फॉर्म यशस्वीरित्या जमा झाला!' : '✓ Survey submitted successfully!');
      setActiveSurveyToFill(null);
      setSurveyFormData({
        pramukh_name: '',
        total_matdar: 1,
        phone: '',
        address: '',
        members: [
          {
            id: 'mem_1',
            full_name: '',
            dob: '',
            age: '',
            has_voter_id: 'YES',
            voter_id_no: '',
            relation: 'कुटुंब प्रमुख (Self)',
          },
        ],
        custom_answers: {},
      });
      loadSurveys();
      loadOverview();
    } catch (err) {
      console.error('Survey submission error:', err);
      // Fallback: If network failed during submission, save offline safely
      if (!isNetworkOnline() || err?.code === 'ERR_NETWORK' || !err?.response) {
        enqueueOfflineItem({
          type: 'SURVEY',
          label_mr: `मतदार सर्व्हे - ${surveyFormData.pramukh_name}`,
          label_en: `Survey - ${surveyFormData.pramukh_name}`,
          payload: {
            published_form_id: activeSurveyToFill?.id,
            surveyId: activeSurveyToFill?.id,
            pramukh_name: surveyFormData.pramukh_name,
            citizenName: surveyFormData.pramukh_name,
            total_matdar: surveyFormData.total_matdar,
            phone: surveyFormData.phone,
            address: surveyFormData.address,
            members: sanitizedMembers,
            custom_answers: surveyFormData.custom_answers,
          }
        });
        alert(language === 'mr'
          ? '📶 नेटवर्क खंडित झाले. सर्व्हे माहिती डिव्हाइसवर सुरक्षित साठवली आहे. इंटरनेट आल्यावर आपोआप सिंक होईल.'
          : '📶 Network disconnected. Survey saved locally and will auto-sync when online.');
        setActiveSurveyToFill(null);
      } else {
        alert(language === 'mr' ? 'सर्व्हे जमा करताना त्रुटी आली. कृपया पुन्हा प्रयत्न करा.' : 'Failed to submit survey. Please try again.');
      }
    } finally {
      setSubmittingSurvey(false);
    }
  };

  // Cast Live Mock Vote in Backend
  const handleCastVote = async (cand) => {
    if (!hasVoted) {
      try {
        await workerApi.castVote(cand.id, cand.name);
        setHasVoted(true);
        setVotedCandidate(cand.name);
        setViewingCandidate(null);
        loadCandidates();
        loadOverview();
      } catch (err) {
        console.error('Vote error:', err);
        alert(err?.response?.data?.message || 'मतदान नोंदवताना अडचण आली.');
      }
    }
  };

  // Helper to render content based on active tab
  const renderContent = () => {
    if (activeTab === 'Dashboard') {
      return (
        <DashboardTab
          isMr={isMr}
          profileData={profileData}
          user={user}
          dashboardStats={dashboardStats}
          complaintsData={complaintsData}
          tasksList={tasksList}
          votersList={votersList}
          attendanceState={attendanceState}
          voterData={voterData}
          activities={activities}
          setActiveTab={setActiveTab}
          LiveClock={LiveClock}
        />
      );
    }

    if (activeTab === 'User Profile') {
      return (
        <ProfileTab
          isMr={isMr}
          t={t}
          user={user}
          profileData={profileData}
          isEditingProfile={isEditingProfile}
          setIsEditingProfile={setIsEditingProfile}
          handleProfileChange={handleProfileChange}
          saveProfile={saveProfile}
          dashboardStats={dashboardStats}
        />
      );
    }

    if (activeTab === 'Contact & Meetings') {
      return (
        <ContactsTab
          isMr={isMr}
          t={t}
          votersList={votersList}
          meetingsList={meetingsList}
          contactTab={contactTab}
          setContactTab={setContactTab}
          contactSentimentFilter={contactSentimentFilter}
          setContactSentimentFilter={setContactSentimentFilter}
          contactSearchQuery={contactSearchQuery}
          setContactSearchQuery={setContactSearchQuery}
          activeVisitContact={activeVisitContact}
          setActiveVisitContact={setActiveVisitContact}
          visitFormData={visitFormData}
          setVisitFormData={setVisitFormData}
          handleSaveVisitLog={handleSaveVisitLog}
          currentCoords={currentCoords}
          isSchedulingMeeting={isSchedulingMeeting}
          setIsSchedulingMeeting={setIsSchedulingMeeting}
          newMeeting={newMeeting}
          setNewMeeting={setNewMeeting}
          handleScheduleMeeting={handleScheduleMeeting}
        />
      );
    }

    if (activeTab === 'Attendance') {
      return (
        <AttendanceTab
          isMr={isMr}
          t={t}
          attendanceHistory={attendanceHistory}
          attendanceState={attendanceState}
          attendancePolicy={attendancePolicy}
          currentCoords={currentCoords}
          locationDetecting={locationDetecting}
          detectCurrentLocation={detectCurrentLocation}
          startPunchIn={startPunchIn}
          startPunchOut={startPunchOut}
          isScanningFace={isScanningFace}
          setIsScanningFace={setIsScanningFace}
          punchMode={punchMode}
          webcamRef={webcamRef}
          webcamError={webcamError}
          setWebcamError={setWebcamError}
          confirmPunch={confirmPunch}
          LiveClock={LiveClock}
        />
      );
    }

    if (activeTab === 'Voting') {
      return <VotingTab user={user} isMr={isMr} isAdmin={false} />;
    }

    if (activeTab === 'Survey') {
      return (
        <SurveyTab
          isMr={isMr}
          language={language}
          t={t}
          surveysList={surveysList}
          surveyStats={surveyStats}
          savedSubmissionsList={savedSubmissionsList}
          surveyActiveView={surveyActiveView}
          setSurveyActiveView={setSurveyActiveView}
          activeSurveyToFill={activeSurveyToFill}
          setActiveSurveyToFill={setActiveSurveyToFill}
          surveyFormData={surveyFormData}
          setSurveyFormData={setSurveyFormData}
          handleSurveySubmit={handleSurveySubmit}
          handleWorkerAddMember={handleWorkerAddMember}
          handleWorkerRemoveMember={handleWorkerRemoveMember}
          handleWorkerMemberChange={handleWorkerMemberChange}
          handleTotalMatdarChange={handleTotalMatdarChange}
          handleCustomAnswerChange={handleCustomAnswerChange}
          handleMultiChoiceToggle={handleMultiChoiceToggle}
          handleOpenSurveyModal={handleOpenSurveyModal}
          submittingSurvey={submittingSurvey}
          checkDuplicateField={checkDuplicateField}
          surveyDuplicateWarning={surveyDuplicateWarning}
          setSurveyDuplicateWarning={setSurveyDuplicateWarning}
        />
      );
    }

    if (activeTab === 'Complaints') {
      return (
        <ComplaintsTab
          isMr={isMr}
          t={t}
          complaintsData={complaintsData}
          complaintFilter={complaintFilter}
          setComplaintFilter={setComplaintFilter}
          complaintSearchQuery={complaintSearchQuery}
          setComplaintSearchQuery={setComplaintSearchQuery}
          handleUpdateComplaintStatus={handleUpdateComplaintStatus}
          isAddingComplaint={isAddingComplaint}
          setIsAddingComplaint={setIsAddingComplaint}
          complaintFormData={complaintFormData}
          setComplaintFormData={setComplaintFormData}
          checkDuplicateField={checkDuplicateField}
          complaintDuplicateWarning={complaintDuplicateWarning}
          setComplaintDuplicateWarning={setComplaintDuplicateWarning}
          handleComplaintSubmit={handleComplaintSubmit}
          currentCoords={currentCoords}
          submittingComplaint={submittingComplaint}
        />
      );
    }

    if (activeTab === 'Tasks') {
      return (
        <TasksTab
          isMr={isMr}
          t={t}
          tasksList={tasksList}
          followUpsList={followUpsList}
          taskActiveTab={taskActiveTab}
          setTaskActiveTab={setTaskActiveTab}
          isAddingTask={isAddingTask}
          setIsAddingTask={setIsAddingTask}
          newTaskData={newTaskData}
          setNewTaskData={setNewTaskData}
          handleCreateTask={handleCreateTask}
          handleToggleTask={handleToggleTask}
          isAddingFollowUp={isAddingFollowUp}
          setIsAddingFollowUp={setIsAddingFollowUp}
          newFollowUpData={newFollowUpData}
          setNewFollowUpData={setNewFollowUpData}
          handleCreateFollowUp={handleCreateFollowUp}
          handleUpdateFollowUpStatus={handleUpdateFollowUpStatus}
        />
      );
    }

    if (activeTab === 'Hierarchy') {
      return <HierarchyTab isMr={isMr} />;
    }

    return (
      <div className="card" style={{ marginTop: '2rem', textAlign: 'center', padding: '4rem 2rem' }}>
        <h2 style={{ fontSize: '1.5rem', color: 'var(--secondary)', marginBottom: '1rem' }}>
          {activeTab}
        </h2>
        <p style={{ color: 'var(--text-secondary)' }}>
          हा विभाग लवकरच थेट डेटाबेसशी जोडला जाईल.
        </p>
      </div>
    );
  };

  const navSections = [
    {
      id: 'core_ops',
      title_mr: 'मुख्य नियंत्रण व उपस्थिती',
      title_en: 'COMMAND & ATTENDANCE',
      items: [
        {
          id: 'Dashboard',
          label_mr: 'डॅशबोर्ड',
          label_en: 'Dashboard',
          icon: LayoutDashboard,
        },
        {
          id: 'Attendance',
          label_mr: 'डिजिटल उपस्थिती',
          label_en: 'Attendance ',
          icon: CalendarCheck,
        },
        {
          id: 'Tasks',
          label_mr: 'कार्ये',
          label_en: 'Tasks',
          icon: ClipboardList,
        },
        {
          id: 'Hierarchy',
          label_mr: 'पदरचना',
          label_en: 'Hierarchy',
          icon: Users,
        },
      ],
    },
    {
      id: 'voter_engagement',
      title_mr: 'मतदार संपर्क',
      title_en: 'VOTER ENGAGEMENT & SURVEYS',
      items: [
        {
          id: 'Survey',
          label_mr: 'सर्वेक्षण',
          label_en: 'Survey',
          icon: ClipboardCheck,
        },
        {
          id: 'Contact & Meetings',
          label_mr: 'नागरिक संपर्क',
          label_en: 'Contact & Meetings',
          icon: Calendar,
        },
        {
          id: 'Complaints',
          label_mr: 'नागरिकांच्या समस्या',
          label_en: 'Citizen Grievances',
          icon: AlertCircle,
        },
      ],
    },
    {
      id: 'election_voting',
      title_mr: 'मतदान कक्ष',
      title_en: 'VOTING',
      items: [
        {
          id: 'Voting',
          label_mr: 'मतदान यंत्र',
          label_en: 'Voting',
          icon: CheckSquare,
        },
      ],
    },
  ];

  const profileNavItem = {
    id: 'User Profile',
    label_mr: 'अधिकृत अधिकारी प्रोफाइल व ओळखपत्र',
    label_en: 'Official Officer Profile & ID',
    icon: User,
  };

  const allItems = [...navSections.flatMap((s) => s.items), profileNavItem];
  const currentNav = allItems.find((item) => item.id === activeTab) || allItems[0];

  // Search Filtering for Navigation
  const filteredSections = navSections
    .map((sec) => {
      if (!searchQuery.trim()) return sec;
      const q = searchQuery.toLowerCase().trim();
      const matched = sec.items.filter(
        (i) =>
          i.label_mr.toLowerCase().includes(q) ||
          i.label_en.toLowerCase().includes(q) ||
          (i.badge && i.badge.toLowerCase().includes(q))
      );
      return { ...sec, items: matched };
    })
    .filter((sec) => sec.items.length > 0);

  return (
    <div
      className="worker-shell"
      style={{
        height: '100dvh',
        maxHeight: '100vh',
        overflow: 'hidden',
        background: '#f8fafc',
        color: '#0f172a',
        fontFamily: isMr ? "'Noto Sans Devanagari', 'Mukta', 'Inter', sans-serif" : "'Inter', 'Noto Sans Devanagari', 'Mukta', sans-serif",
        display: 'flex',
        width: '100vw',
      }}
    >
      {/* Mobile Drawer Backdrop */}
      <div
        className={`worker-sidebar-backdrop ${isSidebarOpen ? 'active' : ''}`}
        onClick={() => setSidebarOpen(false)}
      />

      {/* UNIFIED LIGHT SAAS SIDEBAR */}
      <aside
        className={`worker-sidebar ${isCollapsed ? 'collapsed' : ''} ${isSidebarOpen ? 'mobile-open' : ''}`}
      >
        <div>


          {/* Brand Header Card — Indian Flag Theme */}
          <div
            style={{
              padding: isCollapsed ? '8px 4px' : '12px 12px 10px 12px',
              borderRadius: '10px',
              background: 'rgba(255, 255, 255, 0.08)',
              border: '1px solid rgba(255, 255, 255, 0.15)',
              marginBottom: '14px',
              display: 'flex',
              flexDirection: 'column',
              alignItems: isCollapsed ? 'center' : 'stretch',
              gap: '8px',
              position: 'relative',
              overflow: 'hidden',
              boxShadow: '0 2px 6px rgba(0, 0, 0, 0.2)',
            }}
          >
            {/* Top Indian Tricolor Stripe */}
            <div
              style={{
                position: 'absolute',
                top: 0,
                left: 0,
                right: 0,
                height: '3px',
                background: 'linear-gradient(90deg, #FF671F 0%, #FFFFFF 50%, #138808 100%)',
              }}
            />

            <div style={{ display: 'flex', alignItems: 'center', justifyContent: isCollapsed ? 'center' : 'space-between' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '9px', minWidth: 0 }}>
                {/* Official Indian Flag Emblem with Ashoka Chakra */}
                <div
                  style={{
                    width: '36px',
                    height: '36px',
                    borderRadius: '8px',
                    background: 'linear-gradient(135deg, #0f2b5c 0%, #000080 100%)',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    color: '#ffffff',
                    flexShrink: 0,
                    border: '1px solid rgba(255, 103, 31, 0.4)',
                    boxShadow: '0 2px 8px rgba(0, 0, 128, 0.25)',
                  }}
                  title={isMr ? 'भारत • अधिकारी व बूथ कक्ष' : 'India • Field Officer HQ'}
                >
                  <svg width="22" height="15" viewBox="0 0 36 24" style={{ borderRadius: '2px', boxShadow: '0 1px 3px rgba(0,0,0,0.35)' }}>
                    <rect width="36" height="8" fill="#FF9933" />
                    <rect y="8" width="36" height="8" fill="#FFFFFF" />
                    <rect y="16" width="36" height="8" fill="#138808" />
                    <circle cx="18" cy="12" r="3.2" fill="none" stroke="#000080" strokeWidth="0.7" />
                    <circle cx="18" cy="12" r="0.7" fill="#000080" />
                    {Array.from({ length: 24 }).map((_, i) => (
                      <line
                        key={i}
                        x1="18"
                        y1="12"
                        x2={18 + 3.1 * Math.cos((i * 15 * Math.PI) / 180)}
                        y2={12 + 3.1 * Math.sin((i * 15 * Math.PI) / 180)}
                        stroke="#000080"
                        strokeWidth="0.35"
                      />
                    ))}
                  </svg>
                </div>

                {!isCollapsed && (
                  <div style={{ minWidth: 0 }}>
                    <div
                      style={{
                        color: '#ffffff',
                        fontWeight: '800',
                        fontSize: '13px',
                        lineHeight: 1.2,
                        whiteSpace: 'nowrap',
                        overflow: 'hidden',
                        textOverflow: 'ellipsis',
                      }}
                    >
                      {isMr ? 'अधिकारी व बूथ कक्ष' : 'Field Officer HQ'}
                    </div>
                    <div
                      style={{
                        color: '#ffaa75',
                        fontSize: '10.5px',
                        fontWeight: '700',
                        marginTop: '2px',
                        whiteSpace: 'nowrap',
                        overflow: 'hidden',
                        textOverflow: 'ellipsis',
                      }}
                    >
                      {profileData.assignedWard
                        ? `🇮🇳 ${isMr ? 'प्रभाग' : 'Ward'} ${profileData.assignedWard}`
                        : (isMr ? '🇮🇳 प्रत्यक्ष कार्य संचलन' : '🇮🇳 Field Operations')}
                    </div>
                  </div>
                )}
              </div>

              {/* Collapse / Expand Toggle Button */}
              <button
                type="button"
                onClick={() => setIsCollapsed(!isCollapsed)}
                style={{
                  width: '26px',
                  height: '26px',
                  borderRadius: '6px',
                  background: 'rgba(255, 255, 255, 0.1)',
                  border: '1px solid rgba(255, 255, 255, 0.2)',
                  color: '#ffffff',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  cursor: 'pointer',
                  padding: 0,
                  flexShrink: 0,
                  transition: 'all 0.15s ease',
                }}
                title={isCollapsed ? (isMr ? 'विस्तृत करा' : 'Expand Sidebar') : (isMr ? 'संक्षिप्त करा' : 'Collapse Sidebar')}
              >
                {isCollapsed ? <MoreVertical size={15} /> : <ArrowLeft size={14} />}
              </button>

              {/* Mobile Drawer Close Button */}
              <button
                type="button"
                className="mobile-close-sidebar-btn"
                onClick={() => setSidebarOpen(false)}
                style={{
                  width: '26px',
                  height: '26px',
                  borderRadius: '6px',
                  background: '#fee2e2',
                  border: '1px solid #fecaca',
                  color: '#dc2626',
                  alignItems: 'center',
                  justifyContent: 'center',
                  cursor: 'pointer',
                  padding: 0,
                  flexShrink: 0,
                }}
                title={isMr ? 'मेनू बंद करा' : 'Close Menu'}
              >
                <X size={15} />
              </button>
            </div>

            {/* Offline Alert in Sidebar (ONLY shown when offline) */}
            {!isCollapsed && !isOnline && (
              <div
                style={{
                  paddingTop: '6px',
                  borderTop: '1px solid #fed7aa',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  fontSize: '11px',
                  color: '#ea580c',
                  fontWeight: '700',
                }}
              >
                <span
                  style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: '5px',
                  }}
                >
                  <WifiOff size={12} />
                  {isMr ? 'तुम्ही ऑफलाइन आहात' : 'You are offline'}
                </span>
                {offlineQueueCount > 0 && (
                  <span style={{ fontSize: '10.5px', background: '#ffedd5', padding: '1px 6px', borderRadius: '4px', color: '#9a3412' }}>
                    {offlineQueueCount} {isMr ? 'बाकी' : 'pending'}
                  </span>
                )}
              </div>
            )}
          </div>

          {/* Change Language Button in Sidebar */}
          {!isCollapsed ? (
            <div
              style={{
                display: 'flex',
                alignItems: 'center',
                background: 'rgba(255, 255, 255, 0.08)',
                border: '1px solid rgba(255, 255, 255, 0.15)',
                borderRadius: '8px',
                padding: '3px',
                marginBottom: '12px',
                gap: '2px',
              }}
            >
              <button
                type="button"
                onClick={() => changeLanguage('en')}
                style={{
                  flex: 1,
                  padding: '5px 0',
                  borderRadius: '6px',
                  border: 'none',
                  fontSize: '11.5px',
                  fontWeight: '750',
                  cursor: 'pointer',
                  background: language === 'en' ? 'linear-gradient(135deg, #ff7e2a 0%, #ff5200 100%)' : 'transparent',
                  color: language === 'en' ? '#ffffff' : 'rgba(255, 255, 255, 0.75)',
                  transition: 'all 0.15s ease',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  gap: '4px',
                }}
              >
                <span>English</span>
              </button>
              <button
                type="button"
                onClick={() => changeLanguage('mr')}
                style={{
                  flex: 1,
                  padding: '5px 0',
                  borderRadius: '6px',
                  border: 'none',
                  fontSize: '11.5px',
                  fontWeight: '750',
                  cursor: 'pointer',
                  background: language === 'mr' ? 'linear-gradient(135deg, #ff7e2a 0%, #ff5200 100%)' : 'transparent',
                  color: language === 'mr' ? '#ffffff' : 'rgba(255, 255, 255, 0.75)',
                  transition: 'all 0.15s ease',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  gap: '4px',
                }}
              >
                <span>मराठी</span>
              </button>
            </div>
          ) : (
            <button
              type="button"
              onClick={() => changeLanguage(language === 'en' ? 'mr' : 'en')}
              style={{
                width: '100%',
                padding: '6px 0',
                borderRadius: '7px',
                border: '1px solid rgba(255, 255, 255, 0.2)',
                background: 'rgba(255, 255, 255, 0.1)',
                fontSize: '11px',
                fontWeight: '800',
                color: '#ffaa75',
                cursor: 'pointer',
                marginBottom: '10px',
              }}
              title={language === 'en' ? 'मराठी निवडा' : 'Switch to English'}
            >
              {language === 'en' ? 'EN' : 'मरा'}
            </button>
          )}

          {/* Simple Search Bar (When Expanded) */}
          {!isCollapsed && (
            <div
              style={{
                position: 'relative',
                marginBottom: '12px',
                padding: '0 2px',
              }}
            >
              <div
                style={{
                  position: 'absolute',
                  left: '10px',
                  top: '50%',
                  transform: 'translateY(-50%)',
                  color: '#94a3b8',
                  display: 'flex',
                  alignItems: 'center',
                }}
              >
                <Search size={14} />
              </div>
              <input
                type="text"
                placeholder={isMr ? 'विभाग किंवा कार्य शोधा...' : 'Filter sections...'}
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                style={{
                  width: '100%',
                  boxSizing: 'border-box',
                  padding: '7px 24px 7px 28px',
                  fontSize: '11.5px',
                  borderRadius: '7px',
                  border: '1px solid rgba(255, 255, 255, 0.2)',
                  background: 'rgba(255, 255, 255, 0.08)',
                  color: '#ffffff',
                  outline: 'none',
                  fontFamily: 'inherit',
                }}
              />
              {searchQuery && (
                <button
                  type="button"
                  onClick={() => setSearchQuery('')}
                  style={{
                    position: 'absolute',
                    right: '10px',
                    top: '50%',
                    transform: 'translateY(-50%)',
                    background: 'none',
                    border: 'none',
                    color: '#94a3b8',
                    cursor: 'pointer',
                    fontSize: '12px',
                    padding: 0,
                  }}
                >
                  ✕
                </button>
              )}
            </div>
          )}

          {/* Categorized Navigation Sections */}
          <nav style={{ display: 'flex', flexDirection: 'column', gap: '14px' }}>
            {filteredSections.map((sec) => (
              <div key={sec.id}>
                {!isCollapsed && (
                  <div
                    style={{
                      fontSize: '10.5px',
                      fontWeight: '800',
                      letterSpacing: '0.6px',
                      color: 'rgba(255, 255, 255, 0.55)',
                      textTransform: 'uppercase',
                      padding: '0 8px 4px 8px',
                    }}
                  >
                    {isMr ? sec.title_mr : sec.title_en}
                  </div>
                )}
                <div style={{ display: 'flex', flexDirection: 'column', gap: '2px' }}>
                  {sec.items.map((item) => {
                    const isActive = activeTab === item.id;
                    const IconComp = item.icon;
                    const bTheme = item.badgeTheme ? badgeConfig[item.badgeTheme] || badgeConfig.blue : badgeConfig.blue;

                    return (
                      <button
                        key={item.id}
                        type="button"
                        onClick={() => {
                          setActiveTab(item.id);
                          if (typeof window !== 'undefined' && window.innerWidth <= 992) {
                            setSidebarOpen(false);
                          }
                        }}
                        style={{
                          width: '100%',
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: isCollapsed ? 'center' : 'space-between',
                          padding: isCollapsed ? '9px 0' : '8px 10px',
                          borderRadius: '8px',
                          border: isActive ? '1px solid rgba(255, 126, 42, 0.4)' : '1px solid transparent',
                          borderLeft: isActive ? '3.5px solid #ff5200' : '1px solid transparent',
                          background: isActive ? 'rgba(255, 126, 42, 0.22)' : 'transparent',
                          color: isActive ? '#ffaa75' : 'rgba(255, 255, 255, 0.82)',
                          cursor: 'pointer',
                          fontFamily: 'inherit',
                          fontSize: '12.5px',
                          fontWeight: isActive ? '800' : '600',
                          textAlign: 'left',
                          transition: 'all 0.12s ease',
                          position: 'relative',
                          boxShadow: isActive ? '0 2px 8px rgba(255, 82, 0, 0.18)' : 'none',
                        }}
                        onMouseOver={(e) => {
                          if (!isActive) {
                            e.currentTarget.style.background = 'rgba(255, 255, 255, 0.1)';
                            e.currentTarget.style.color = '#ffffff';
                          }
                        }}
                        onMouseOut={(e) => {
                          if (!isActive) {
                            e.currentTarget.style.background = 'transparent';
                            e.currentTarget.style.color = 'rgba(255, 255, 255, 0.82)';
                          }
                        }}
                        title={isMr ? item.label_mr : item.label_en}
                      >
                        <div style={{ display: 'flex', alignItems: 'center', gap: '9px', minWidth: 0 }}>
                          <span
                            style={{
                              color: isActive ? '#ffaa75' : 'rgba(255, 255, 255, 0.75)',
                              display: 'flex',
                              alignItems: 'center',
                              justifyContent: 'center',
                              flexShrink: 0,
                            }}
                          >
                            <IconComp size={17} />
                          </span>
                          {!isCollapsed && (
                            <span
                              style={{
                                whiteSpace: 'nowrap',
                                overflow: 'hidden',
                                textOverflow: 'ellipsis',
                              }}
                            >
                              {isMr ? item.label_mr : item.label_en}
                            </span>
                          )}
                        </div>

                        {!isCollapsed && item.badge && (
                          <span
                            style={{
                              fontSize: '10px',
                              fontWeight: '750',
                              padding: '2px 6px',
                              borderRadius: '9999px',
                              background: bTheme.bg,
                              color: bTheme.text,
                              border: `1px solid ${bTheme.border}`,
                              whiteSpace: 'nowrap',
                            }}
                          >
                            {item.badge}
                          </span>
                        )}
                      </button>
                    );
                  })}
                </div>
              </div>
            ))}
          </nav>
        </div>

        {/* Sidebar Footer with Worker Identity Strip */}
        <div
          style={{
            marginTop: '16px',
            paddingTop: '10px',
            borderTop: '1px solid #e2e8f0',
            display: 'flex',
            flexDirection: 'column',
            gap: '8px',
          }}
        >
          {/* Clickable Profile & ID Card Trigger */}
          <div
            onClick={() => {
              setActiveTab('User Profile');
              if (typeof window !== 'undefined' && window.innerWidth <= 992) {
                setSidebarOpen(false);
              }
            }}
            role="button"
            tabIndex={0}
            title={isMr ? 'अधिकृत अधिकारी प्रोफाइल व ओळखपत्र उघडा' : 'View Official Officer Profile & ID Card'}
            style={{
              display: 'flex',
              alignItems: 'center',
              gap: '8px',
              padding: isCollapsed ? '6px 0' : '8px 10px',
              borderRadius: '9px',
              background: activeTab === 'User Profile' ? 'rgba(255, 126, 42, 0.22)' : 'rgba(255, 255, 255, 0.08)',
              border: activeTab === 'User Profile' ? '1.5px solid #ff5200' : '1px solid rgba(255, 255, 255, 0.15)',
              justifyContent: isCollapsed ? 'center' : 'space-between',
              boxShadow: activeTab === 'User Profile' ? '0 2px 8px rgba(255, 82, 0, 0.2)' : '0 2px 6px rgba(0, 0, 0, 0.15)',
              cursor: 'pointer',
              transition: 'all 0.15s ease',
            }}
            onMouseOver={(e) => {
              if (activeTab !== 'User Profile') {
                e.currentTarget.style.background = 'rgba(255, 255, 255, 0.14)';
              }
            }}
            onMouseOut={(e) => {
              if (activeTab !== 'User Profile') {
                e.currentTarget.style.background = 'rgba(255, 255, 255, 0.08)';
              }
            }}
          >
            <div style={{ display: 'flex', alignItems: 'center', gap: '8px', minWidth: 0, flex: 1 }}>
              <div
                style={{
                  width: '32px',
                  height: '32px',
                  borderRadius: '50%',
                  background: 'linear-gradient(135deg, #ff7e2a 0%, #ff5200 100%)',
                  color: '#ffffff',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  fontSize: '13px',
                  fontWeight: '800',
                  flexShrink: 0,
                  boxShadow: '0 2px 6px rgba(255, 82, 0, 0.35)',
                }}
              >
                {(profileData.fullName || user?.full_name || 'K').charAt(0)}
              </div>
              {!isCollapsed && (
                <div style={{ minWidth: 0, flex: 1 }}>
                  <div
                    style={{
                      fontSize: '12.5px',
                      fontWeight: '800',
                      color: '#ffffff',
                      whiteSpace: 'nowrap',
                      overflow: 'hidden',
                      textOverflow: 'ellipsis',
                    }}
                  >
                    {profileData.fullName || user?.full_name || 'अधिकारी'}
                  </div>
                  <div style={{ fontSize: '11px', color: '#ffaa75', fontWeight: '600' }}>
                    {isMr ? 'प्रोफाइल' : 'Profile'}
                  </div>
                </div>
              )}
            </div>
            {!isCollapsed && (
              <ChevronRight size={14} style={{ color: activeTab === 'User Profile' ? '#ffaa75' : 'rgba(255, 255, 255, 0.6)', flexShrink: 0 }} />
            )}
          </div>

          <button
            type="button"
            onClick={handleLogout}
            style={{
              width: '100%',
              background: 'rgba(239, 68, 68, 0.16)',
              border: '1px solid rgba(239, 68, 68, 0.35)',
              color: '#fca5a5',
              padding: isCollapsed ? '8px 0' : '7px 10px',
              borderRadius: '7px',
              fontSize: '12px',
              fontWeight: '700',
              cursor: 'pointer',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              gap: '6px',
              fontFamily: 'inherit',
              transition: 'all 0.15s ease',
            }}
            onMouseOver={(e) => {
              e.currentTarget.style.background = 'rgba(239, 68, 68, 0.28)';
              e.currentTarget.style.color = '#ffffff';
            }}
            onMouseOut={(e) => {
              e.currentTarget.style.background = 'rgba(239, 68, 68, 0.16)';
              e.currentTarget.style.color = '#fca5a5';
            }}
            title={t('signout')}
          >
            <span>🚪</span>
            {!isCollapsed && <span>{t('signout')}</span>}
          </button>
        </div>
      </aside>

      {/* MAIN CONTENT WRAPPER */}
      <main
        style={{
          flex: 1,
          display: 'flex',
          flexDirection: 'column',
          minWidth: 0,
          background: '#f8fafc',
          position: 'relative',
          height: '100dvh',
          maxHeight: '100vh',
          overflowY: 'auto',
          overflowX: 'hidden',
          scrollBehavior: 'smooth',
          WebkitOverflowScrolling: 'touch',
        }}
      >
        {/* RESPONSIVE MOBILE & TABLET TOP HEADER (< 992px) */}
        <header className="worker-mobile-top-header">
          <div className="mobile-header-left">
            <button
              type="button"
              className="mobile-hamburger-btn"
              onClick={() => setSidebarOpen(true)}
              aria-label="Open navigation menu"
              title={isMr ? 'मेनू उघडा' : 'Open Menu'}
            >
              <Menu size={22} />
            </button>
            <div className="mobile-brand-info">
              <span className="mobile-brand-title">
                {isMr ? 'अधिकारी कक्ष' : 'Officer Portal'}
              </span>
              <span className="mobile-active-tab-badge">
                {currentNav ? (isMr ? currentNav.label_mr : currentNav.label_en) : activeTab}
              </span>
            </div>
          </div>

          <div className="mobile-header-right">
            <span
              className={`mobile-status-pill ${isOnline ? 'online' : 'offline'}`}
              title={isOnline ? (isMr ? 'ऑनलाइन' : 'Online') : (isMr ? 'ऑफलाइन' : 'Offline')}
            >
              <span className="status-dot" />
              <span className="status-text">{isOnline ? (isMr ? 'ऑन' : 'Online') : (isMr ? 'ऑफ' : 'Offline')}</span>
            </span>

            <button
              type="button"
              className="mobile-lang-btn"
              onClick={() => changeLanguage(language === 'mr' ? 'en' : 'mr')}
              title={isMr ? 'इंग्रजीत बदला' : 'Switch to Marathi'}
            >
              {language === 'mr' ? 'EN' : 'मराठी'}
            </button>

            <div
              className="mobile-user-avatar"
              onClick={() => setActiveTab('User Profile')}
              title={isMr ? 'माझे प्रोफाइल' : 'My Profile'}
            >
              {(profileData.fullName || user?.full_name || 'K').charAt(0)}
            </div>
          </div>
        </header>

        {/* Offline Alert Banner (ONLY shown when user is offline) */}
        {!isOnline && (
          <div
            style={{
              background: '#fff7ed',
              borderBottom: '1px solid #fed7aa',
              color: '#c2410c',
              padding: '10px 20px',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between',
              gap: '12px',
              fontSize: '13px',
              fontWeight: '700',
              boxShadow: '0 2px 4px rgba(234, 88, 12, 0.04)',
            }}
          >
            <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
              <WifiOff size={16} style={{ color: '#ea580c', flexShrink: 0 }} />
              <span>
                {isMr
                  ? '⚠️ तुम्ही सध्या ऑफलाइन आहात. सर्व माहिती या डिव्हाइसवर सुरक्षित साठवली जात आहे.'
                  : '⚠️ You are currently offline. All data is being saved securely to this device.'}
              </span>
            </div>
            {offlineQueueCount > 0 && (
              <span
                style={{
                  background: '#ffedd5',
                  padding: '3px 9px',
                  borderRadius: '6px',
                  fontSize: '11.5px',
                  color: '#9a3412',
                  fontWeight: '800',
                  whiteSpace: 'nowrap',
                }}
              >
                {isMr ? `${offlineQueueCount} नोंदी प्रलंबित` : `${offlineQueueCount} pending`}
              </span>
            )}
          </div>
        )}

        {/* Connection Restored Sync Banner (ONLY when online and has queued items) */}
        {isOnline && offlineQueueCount > 0 && (
          <div
            style={{
              background: '#fef3c7',
              borderBottom: '1px solid #fde68a',
              color: '#92400e',
              padding: '8px 20px',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between',
              gap: '10px',
              fontSize: '12.5px',
              fontWeight: '700',
            }}
          >
            <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
              <RefreshCw size={14} style={{ color: '#b45309', animation: isSyncing ? 'spin 1s linear infinite' : 'none' }} />
              <span>
                {isMr
                  ? `जोडणी पूर्ववत झाली. ${offlineQueueCount} ऑफलाइन नोंदी डेटाबेसमध्ये पाठवण्यासाठी सिंक करा.`
                  : `Connection restored. Sync ${offlineQueueCount} pending offline records to database.`}
              </span>
            </div>
            <button
              type="button"
              onClick={() => handleSyncQueue(false)}
              disabled={isSyncing}
              style={{
                background: '#ea580c',
                color: '#ffffff',
                border: 'none',
                borderRadius: '6px',
                padding: '5px 12px',
                fontSize: '12px',
                fontWeight: '750',
                cursor: isSyncing ? 'wait' : 'pointer',
              }}
            >
              {isSyncing ? (isMr ? 'सिंक होत आहे...' : 'Syncing...') : (isMr ? 'आता सिंक करा' : 'Sync Now')}
            </button>
          </div>
        )}

        {/* PAGE CONTENT CONTAINER */}
        <div className="worker-content-container">
          {renderContent()}
        </div>


        {/* MOBILE BOTTOM NAVIGATION BAR */}
        <nav className="worker-bottom-nav">
          <button
            type="button"
            className={`worker-bottom-nav-item ${activeTab === 'Dashboard' ? 'active' : ''}`}
            onClick={() => setActiveTab('Dashboard')}
          >
            <span className="icon"><LayoutDashboard size={20} /></span>
            <span>{isMr ? 'डॅशबोर्ड' : 'Dashboard'}</span>
          </button>
          <button
            type="button"
            className={`worker-bottom-nav-item ${activeTab === 'Attendance' ? 'active' : ''}`}
            onClick={() => setActiveTab('Attendance')}
          >
            <span className="icon"><CalendarCheck size={20} /></span>
            <span>{isMr ? 'हजेरी' : 'Attendance'}</span>
          </button>
          <button
            type="button"
            className={`worker-bottom-nav-item ${activeTab === 'Contact & Meetings' ? 'active' : ''}`}
            onClick={() => setActiveTab('Contact & Meetings')}
          >
            <span className="icon"><BookUser size={20} /></span>
            <span>{isMr ? 'संपर्क' : 'Contacts'}</span>
          </button>
          <button
            type="button"
            className={`worker-bottom-nav-item ${activeTab === 'Complaints' ? 'active' : ''}`}
            onClick={() => setActiveTab('Complaints')}
          >
            <span className="icon" style={{ position: 'relative', display: 'inline-flex', alignItems: 'center', justifyContent: 'center' }}>
              <AlertCircle size={20} />
              <span style={{ position: 'absolute', top: '-3px', right: '-4px', width: '7px', height: '7px', borderRadius: '50%', background: '#ef4444' }} />
            </span>
            <span>{isMr ? 'तक्रारी' : 'Complaints'}</span>
          </button>
          <button
            type="button"
            className="worker-bottom-nav-item"
            onClick={() => setSidebarOpen(true)}
            title={isMr ? 'अधिक मेनू' : 'More Menu'}
          >
            <span className="icon"><MoreHorizontal size={22} /></span>
            <span>{isMr ? 'अधिक' : 'More'}</span>
          </button>
        </nav>
      </main>
    </div>
  );
}
