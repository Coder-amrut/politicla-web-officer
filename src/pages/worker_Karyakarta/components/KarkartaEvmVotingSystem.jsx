import React, { useState, useEffect, useRef, useMemo } from 'react';
import { workerApi } from '../workerApi';
import voteSongAudio from '../../../assets/vote_song.mp3';
import wrongSoundAudio from '../../../assets/wrong_sound.mp3';
import { isNetworkOnline, enqueueOfflineItem } from '../offlineSyncManager';
import {
  Check,
  Share2,
  RotateCcw,
  Volume2,
  VolumeX,
  Smartphone,
  Plus,
  Edit2,
  Trash2,
  ExternalLink,
  Copy,
  Award,
  BarChart2,
  Users,
  Settings,
  ChevronRight,
  AlertCircle,
  X,
  Radio,
  Sparkles,
  Play,
  Square,
  Clock,
  Lock,
  CheckCircle2,
  Filter,
  Calendar
} from 'lucide-react';

// Bulletproof, anti-flicker media image component
// Keeps error state locally so React doesn't fight DOM mutations
// and prevents infinite onError loops or white-flash blinking.
const EvmMediaImage = React.memo(function EvmMediaImage({
  src,
  alt,
  style,
  fallbackChar,
  isSymbol = false
}) {
  const [hasError, setHasError] = useState(false);

  useEffect(() => {
    setHasError(false);
  }, [src]);

  if (!src || hasError) {
    if (fallbackChar) {
      return (
        <div
          style={{
            ...style,
            display: 'inline-flex',
            alignItems: 'center',
            justifyContent: 'center',
            background: '#f1f5f9',
            color: '#475569',
            fontWeight: '800',
            fontSize: '15px'
          }}
          title={alt}
        >
          {fallbackChar}
        </div>
      );
    }
    return (
      <div
        style={{
          ...style,
          display: 'inline-flex',
          alignItems: 'center',
          justifyContent: 'center',
          background: '#f8fafc',
          color: '#64748b',
          fontSize: isSymbol ? '20px' : '15px',
          fontWeight: '700'
        }}
        title={alt}
      >
        {isSymbol ? '🗳️' : '👤'}
      </div>
    );
  }

  return (
    <img
      src={src}
      alt={alt}
      style={style}
      loading="lazy"
      onError={() => setHasError(true)}
    />
  );
});

function KarkartaEvmVotingSystem({ user, isMr = true, isAdmin: isAdminProp }) {
  // Determine if current user has Admin privileges
  const isAdmin = Boolean(
    isAdminProp !== undefined
      ? isAdminProp
      : (
        user?.roles?.some(r => {
          const roleStr = typeof r === 'string' ? r : r?.name || '';
          return roleStr.toUpperCase() === 'ADMIN' || roleStr.toUpperCase() === 'ADMINISTRATOR';
        }) ||
        user?.role?.toUpperCase?.() === 'ADMIN' ||
        user?.role?.toUpperCase?.() === 'ADMINISTRATOR'
      )
  );

  // Mode selection: 'evm' (Simulator), 'manage' (Candidates list & edit), 'results' (Live tally), 'settings' (Election settings)
  // Non-admin (Karyakarta) is strictly locked into 'evm' (voting simulator)
  const [activeMode, setActiveMode] = useState('evm');
  const currentMode = isAdmin ? activeMode : 'evm';
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  // Election, Candidates, Settings State
  const [election, setElection] = useState(null);
  const [candidates, setCandidates] = useState([]);
  const [settings, setSettings] = useState({
    enable_sound: true,
    enable_vibration: true,
    allow_multiple_votes: false,
    contact_whatsapp: '9822011231',
    contact_call: '9822011231'
  });
  const [totalVotes, setTotalVotes] = useState(0);

  // Worker Single Vote State & All Votes Ledger (Admin Audit)
  const [hasVoted, setHasVoted] = useState(false);
  const [workerVote, setWorkerVote] = useState(null);
  const [allVotes, setAllVotes] = useState([]);
  const [candidateLedgerFilter, setCandidateLedgerFilter] = useState('all');

  // Election Timer State (Day / Hr / Min / Sec)
  const calculateTimeLeft = (endTimeStr, remainingSecs) => {
    if (typeof remainingSecs === 'number' && remainingSecs >= 0) {
      if (remainingSecs <= 0) return { days: 0, hours: 0, minutes: 0, seconds: 0, totalSeconds: 0, isExpired: true };
      const days = Math.floor(remainingSecs / 86400);
      const hours = Math.floor((remainingSecs % 86400) / 3600);
      const minutes = Math.floor((remainingSecs % 3600) / 60);
      const seconds = remainingSecs % 60;
      return { days, hours, minutes, seconds, totalSeconds: remainingSecs, isExpired: false };
    }
    if (!endTimeStr) return { days: 0, hours: 0, minutes: 0, seconds: 0, totalSeconds: 0, isExpired: true };
    const target = new Date(endTimeStr).getTime();
    const diff = target - Date.now();
    if (diff <= 0 || isNaN(diff)) {
      return { days: 0, hours: 0, minutes: 0, seconds: 0, totalSeconds: 0, isExpired: true };
    }
    const totalSeconds = Math.floor(diff / 1000);
    const days = Math.floor(totalSeconds / 86400);
    const hours = Math.floor((totalSeconds % 86400) / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;
    return { days, hours, minutes, seconds, totalSeconds, isExpired: false };
  };

  const [timeLeft, setTimeLeft] = useState({ days: 0, hours: 0, minutes: 0, seconds: 0, totalSeconds: 0, isExpired: false });
  const [isStartElectionModalOpen, setIsStartElectionModalOpen] = useState(false);
  const [timerFormData, setTimerFormData] = useState({
    name: '२८२ - सांगली विधानसभा / नगर परिषद निवडणूक २०२६',
    days: 0,
    hours: 4,
    minutes: 0,
    seconds: 0,
    reset_votes: true
  });
  const [isStartingElection, setIsStartingElection] = useState(false);
  const [isEndingElection, setIsEndingElection] = useState(false);

  // Voting Simulator State
  const [votedCandidate, setVotedCandidate] = useState(null);
  const [votedCandidateId, setVotedCandidateId] = useState(null);
  const [isBeeping, setIsBeeping] = useState(false);
  const [recentVotes, setRecentVotes] = useState([]);
  const [voteSuccessModalCandidate, setVoteSuccessModalCandidate] = useState(null);

  // Candidate Form Modal State (Add / Edit)
  const [isCandidateModalOpen, setIsCandidateModalOpen] = useState(false);
  const [editingCandidate, setEditingCandidate] = useState(null);
  const [candidateFormData, setCandidateFormData] = useState({
    serial_number: '',
    candidate_name: '',
    party_name: '',
    candidate_photo: '',
    party_symbol: '',
    position: 'विधानसभा आमदार',
    section_color: '#ea580c',
    highlighted: false,
    voice_message: '',
  });
  const [isSavingCandidate, setIsSavingCandidate] = useState(false);

  // Settings Edit Form
  const [electionFormData, setElectionFormData] = useState({
    name: '',
    message: '',
    date_time: '',
    whatsapp_number: ''
  });

  // Audio references
  const audioRef = useRef(null);
  const wrongAudioRef = useRef(null);

  // Load live EVM data from Laravel backend (karkarta_ tables)
  const loadEvmData = async () => {
    try {
      setLoading(true);
      setError('');
      const data = await workerApi.getEvmElectionData();
      if (data) {
        setElection(data.election);
        setCandidates(data.candidates || []);
        if (data.settings) setSettings(data.settings);
        setTotalVotes(data.totalVotes || 0);
        setHasVoted(Boolean(data.hasVoted));
        setWorkerVote(data.workerVote || null);
        setAllVotes(data.allVotes || []);

        if (data.election?.status === 'ACTIVE' && (data.election?.end_time_iso || data.election?.end_time)) {
          setTimeLeft(calculateTimeLeft(data.election.end_time_iso || data.election.end_time, data.election.remaining_seconds));
        } else {
          setTimeLeft(calculateTimeLeft(null));
        }

        if (data.election) {
          setElectionFormData({
            name: data.election.name || '',
            message: data.election.message || '',
            date_time: data.election.date_time || '',
            whatsapp_number: data.election.whatsapp_number || ''
          });
          setTimerFormData(prev => ({
            ...prev,
            name: data.election.name || prev.name
          }));
        }
      }
    } catch (err) {
      console.error('Error loading EVM data:', err);
      setError('डेटाबेसकडून EVM माहिती मिळवताना अडचण आली.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadEvmData();
  }, []);

  // Timer interval for real-time countdown and auto-expiration
  useEffect(() => {
    if (!election?.end_time || election?.status !== 'ACTIVE') {
      setTimeLeft(calculateTimeLeft(null));
      return;
    }

    const endStr = election.end_time_iso || election.end_time;
    const targetTime = new Date(endStr).getTime();

    const updateCountdown = () => {
      const diff = targetTime - Date.now();
      if (diff <= 0 || isNaN(diff)) {
        setTimeLeft({ days: 0, hours: 0, minutes: 0, seconds: 0, totalSeconds: 0, isExpired: true });
        setElection(prev => prev ? { ...prev, status: 'ENDED' } : prev);
      } else {
        const totalSeconds = Math.floor(diff / 1000);
        const days = Math.floor(totalSeconds / 86400);
        const hours = Math.floor((totalSeconds % 86400) / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;
        setTimeLeft({ days, hours, minutes, seconds, totalSeconds, isExpired: false });
      }
    };

    updateCountdown();
    const timer = setInterval(updateCountdown, 1000);
    return () => clearInterval(timer);
  }, [election?.end_time, election?.end_time_iso, election?.status]);

  // Admin: Start Election with Timer
  const handleStartElection = async (e) => {
    if (e) e.preventDefault();
    try {
      setIsStartingElection(true);
      const res = await workerApi.startEvmElection({
        name: timerFormData.name,
        days: Number(timerFormData.days) || 0,
        hours: Number(timerFormData.hours) || 0,
        minutes: Number(timerFormData.minutes) || 0,
        seconds: Number(timerFormData.seconds) || 0,
        reset_votes: Boolean(timerFormData.reset_votes)
      });
      if (res?.success) {
        setIsStartElectionModalOpen(false);
        if (res.data) {
          setElection(res.data);
          setTimeLeft(calculateTimeLeft(res.data.end_time_iso || res.data.end_time, res.data.remaining_seconds));
        }
        await loadEvmData();
        alert(isMr ? 'निवडणूक अधिकृतपणे डेटाबेसमध्ये अपडेट व सुरू झाली आहे! मतदान टाइमर सक्रिय झाला.' : 'Election has been updated in database and officially started! Countdown timer is live.');
      } else {
        alert(res?.message || 'निवडणूक सुरू करताना त्रुटी आली.');
      }
    } catch (err) {
      console.error('Error starting election:', err);
      alert(err?.response?.data?.message || 'निवडणूक सुरू करताना त्रुटी आली.');
    } finally {
      setIsStartingElection(false);
    }
  };

  // Admin: End Election
  const handleEndElection = async () => {
    if (!window.confirm(isMr ? 'आपणास खात्री आहे का? निवडणूक त्वरित समाप्त केली जाईल आणि सर्व मतदान बंद होईल.' : 'Are you sure you want to end the election now? All voting will be stopped.')) {
      return;
    }
    try {
      setIsEndingElection(true);
      const res = await workerApi.endEvmElection();
      if (res?.success) {
        await loadEvmData();
        alert(isMr ? 'निवडणूक समाप्त करण्यात आली आहे. मतदान बंद झाले.' : 'Election has been ended. Voting is now closed.');
      }
    } catch (err) {
      console.error('Error ending election:', err);
      alert('निवडणूक समाप्त करताना त्रुटी आली.');
    } finally {
      setIsEndingElection(false);
    }
  };

  // Web Audio API synthesized EVM Beep (Backup in case mp3 doesn't autoplay)
  const playSyntheticEvmBeep = () => {
    try {
      const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      const osc = audioCtx.createOscillator();
      const gain = audioCtx.createGain();
      osc.type = 'sawtooth';
      osc.frequency.setValueAtTime(1050, audioCtx.currentTime); // Standard EVM beep frequency
      gain.gain.setValueAtTime(0.4, audioCtx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 1.8);
      osc.connect(gain);
      gain.connect(audioCtx.destination);
      osc.start();
      osc.stop(audioCtx.currentTime + 1.8);
    } catch (e) {
      console.warn('AudioContext not supported:', e);
    }
  };

  // Play EVM Vote Sound
  const playVoteSound = () => {
    if (!settings.enable_sound) return;
    try {
      if (audioRef.current) {
        audioRef.current.currentTime = 0;
        audioRef.current.volume = 0.85;
        const promise = audioRef.current.play();
        if (promise !== undefined) {
          promise.catch(() => playSyntheticEvmBeep());
        }
      } else {
        playSyntheticEvmBeep();
      }
    } catch (e) {
      playSyntheticEvmBeep();
    }
  };

  // Play Wrong Sound
  const playWrongSound = () => {
    if (!settings.enable_sound) return;
    try {
      if (wrongAudioRef.current) {
        wrongAudioRef.current.currentTime = 0;
        wrongAudioRef.current.volume = 0.8;
        wrongAudioRef.current.play().catch(() => { });
      }
    } catch (e) { }
  };

  // Device Vibration
  const triggerVibration = () => {
    if (!settings.enable_vibration) return;
    if (typeof window !== 'undefined' && 'vibrate' in navigator) {
      navigator.vibrate([400, 100, 400]);
    }
  };

  // Marathi / Hindi Text-To-Speech announcement
  const speakVoiceMessage = (text) => {
    if (typeof window === 'undefined' || !('speechSynthesis' in window) || !text) return;
    try {
      window.speechSynthesis.cancel();
      const utterance = new SpeechSynthesisUtterance(text);
      utterance.lang = 'mr-IN';
      utterance.rate = 0.95;
      utterance.pitch = 1.0;
      const voices = window.speechSynthesis.getVoices();
      const mrVoice = voices.find(v => v.lang.includes('mr') || v.lang.includes('hi'));
      if (mrVoice) utterance.voice = mrVoice;
      window.speechSynthesis.speak(utterance);
    } catch (e) {
      console.warn('SpeechSynthesis error:', e);
    }
  };

  // Handle EVM Vote Press
  const handleVotePress = async (candidate) => {
    if (!candidate) {
      playWrongSound();
      triggerVibration();
      return;
    }

    // Check if election is active and timer has not expired
    if (election?.status !== 'ACTIVE' || timeLeft.isExpired) {
      playWrongSound();
      triggerVibration();
      alert(isMr ? 'निवडणूक सध्या बंद आहे किंवा वेळ संपली आहे. आता मतदान करता येणार नाही.' : 'Election is currently closed or timer has expired. Voting is unavailable.');
      return;
    }

    // Worker can only vote ONCE per election
    if (!isAdmin && hasVoted) {
      playWrongSound();
      triggerVibration();
      const votedCand = candidates.find(c => String(c.id) === String(workerVote?.candidate_id)) || (workerVote ? {
        ...workerVote,
        id: workerVote.candidate_id,
        candidate_name: workerVote.candidate_name
      } : candidate);
      setVoteSuccessModalCandidate(votedCand);
      return;
    }

    // Trigger visual and auditory signals
    setVotedCandidateId(candidate.id);
    setIsBeeping(true);
    playVoteSound();
    triggerVibration();

    // Voice announcement
    const msg = candidate.voice_message ||
      `आपले मत ${candidate.candidate_name} यांना नोंदवले गेले आहे. धन्यवाद!`;
    setTimeout(() => {
      speakVoiceMessage(msg);
    }, 600);

    // Call backend API or queue offline if disconnected
    try {
      if (!isNetworkOnline()) {
        enqueueOfflineItem({
          type: 'EVM_VOTE',
          label_mr: `EVM मत - ${candidate.candidate_name}`,
          label_en: `EVM Vote - ${candidate.candidate_name}`,
          payload: {
            candidateId: candidate.id,
            candidateName: candidate.candidate_name,
            electionId: election?.id,
            voterName: user?.full_name || 'बूथ मतदार (Offline)',
            boothNo: 'सांगली मध्य बूथ क्र. १'
          }
        });
        setVotedCandidate(candidate);
        setHasVoted(true);
        setWorkerVote({
          candidate_id: candidate.id,
          candidate_name: candidate.candidate_name,
          party_name: candidate.party_name,
          party_symbol: candidate.party_symbol,
          candidate_photo: candidate.candidate_photo,
          serial_number: candidate.serial_number,
          position: candidate.position,
          voted_at: new Date().toISOString()
        });
        setRecentVotes(prev => [candidate, ...prev.slice(0, 4)]);
        setTotalVotes(prev => prev + 1);
        setCandidates(prev => prev.map(c => c.id === candidate.id ? { ...c, votes: (c.votes || 0) + 1 } : c));
        setTimeout(() => {
          setVoteSuccessModalCandidate(candidate);
        }, 1100);
      } else {
        const res = await workerApi.castEvmVote({
          candidateId: candidate.id,
          candidateName: candidate.candidate_name,
          electionId: election?.id,
          voterName: user?.full_name || 'क्षेत्र अधिकारी',
          boothNo: 'सांगली मध्य बूथ क्र. १'
        });

        if (res?.success) {
          setVotedCandidate(candidate);
          setHasVoted(true);
          setWorkerVote({
            candidate_id: candidate.id,
            candidate_name: candidate.candidate_name,
            party_name: candidate.party_name,
            party_symbol: candidate.party_symbol,
            candidate_photo: candidate.candidate_photo,
            serial_number: candidate.serial_number,
            position: candidate.position,
            voted_at: new Date().toISOString()
          });
          setRecentVotes(prev => [candidate, ...prev.slice(0, 4)]);
          setTotalVotes(prev => prev + 1);
          setCandidates(prev => prev.map(c => c.id === candidate.id ? { ...c, votes: (c.votes || 0) + 1 } : c));
          loadEvmData();
          setTimeout(() => {
            setVoteSuccessModalCandidate(candidate);
          }, 1100);
        } else if (res?.alreadyVoted) {
          setHasVoted(true);
          const votedCand = candidates.find(c => String(c.id) === String(workerVote?.candidate_id)) || candidate;
          setVoteSuccessModalCandidate(votedCand);
        }
      }
    } catch (err) {
      console.warn('Online vote error:', err);
      if (err?.response?.data?.alreadyVoted) {
        setHasVoted(true);
        const votedCand = candidates.find(c => String(c.id) === String(workerVote?.candidate_id)) || candidate;
        setVoteSuccessModalCandidate(votedCand);
      } else {
        enqueueOfflineItem({
          type: 'EVM_VOTE',
          label_mr: `EVM मत - ${candidate.candidate_name}`,
          label_en: `EVM Vote - ${candidate.candidate_name}`,
          payload: {
            candidateId: candidate.id,
            candidateName: candidate.candidate_name,
            electionId: election?.id,
            voterName: user?.full_name || 'बूथ मतदार (Offline)',
            boothNo: 'सांगली मध्य बूथ क्र. १'
          }
        });
        setVotedCandidate(candidate);
        setHasVoted(true);
        setWorkerVote({
          candidate_id: candidate.id,
          candidate_name: candidate.candidate_name,
          party_name: candidate.party_name,
          party_symbol: candidate.party_symbol,
          candidate_photo: candidate.candidate_photo,
          serial_number: candidate.serial_number,
          position: candidate.position,
          voted_at: new Date().toISOString()
        });
        setRecentVotes(prev => [candidate, ...prev.slice(0, 4)]);
        setTotalVotes(prev => prev + 1);
        setCandidates(prev => prev.map(c => c.id === candidate.id ? { ...c, votes: (c.votes || 0) + 1 } : c));
        setTimeout(() => {
          setVoteSuccessModalCandidate(candidate);
        }, 1100);
      }
    }

    // After brief delay matching real EVM lock, turn off beep and reset EVM ready state
    setTimeout(() => {
      setIsBeeping(false);
      setVotedCandidateId(null);
    }, 2000);
  };

  // Reset Session (Wipes votes from DB to start a fresh voter training session)
  const handleResetSession = async () => {
    if (!window.confirm(isMr ? 'सर्व मतदानाचे आकडे रीसेट करून नवीन प्रात्यक्षिक सत्र सुरू करायचे का?' : 'Reset all vote counts and begin a fresh polling demo session?')) {
      return;
    }
    try {
      setLoading(true);
      await workerApi.resetEvmVotes();
      await loadEvmData();
      setVotedCandidate(null);
      setVotedCandidateId(null);
      setShowConfirmationModal(false);
      alert(isMr ? 'डेमो मतदानाचे आकडे रीसेट झाले!' : 'Vote counters reset successfully!');
    } catch (err) {
      alert('रीसेट करताना त्रुटी आली.');
    } finally {
      setLoading(false);
    }
  };

  // Candidate Add / Edit Submit
  const handleSaveCandidate = async (e) => {
    e.preventDefault();
    if (!candidateFormData.candidate_name.trim()) {
      alert(isMr ? 'कृपया उमेदवाराचे नाव भरा.' : 'Please enter candidate name.');
      return;
    }

    setIsSavingCandidate(true);
    try {
      if (editingCandidate) {
        await workerApi.updateEvmCandidate(editingCandidate.id, candidateFormData);
        alert(isMr ? 'उमेदवाराची माहिती अद्यतनित झाली!' : 'Candidate updated successfully!');
      } else {
        await workerApi.addEvmCandidate(candidateFormData);
        alert(isMr ? 'नवीन उमेदवार EVM बॅलेटवर जोडला गेला!' : 'Candidate added to EVM ballot!');
      }
      setIsCandidateModalOpen(false);
      setEditingCandidate(null);
      loadEvmData();
    } catch (err) {
      console.error('Candidate save error:', err);
      alert('उमेदवार जतन करताना त्रुटी आली.');
    } finally {
      setIsSavingCandidate(false);
    }
  };

  // Open Edit Modal
  const openEditCandidateModal = (cand) => {
    setEditingCandidate(cand);
    setCandidateFormData({
      serial_number: cand.serial_number || '',
      candidate_name: cand.candidate_name || '',
      party_name: cand.party_name || '',
      candidate_photo: cand.candidate_photo || '',
      party_symbol: cand.party_symbol || '',
      position: cand.position || 'विधानसभा आमदार',
      section_color: cand.section_color || '#ea580c',
      highlighted: Boolean(cand.highlighted),
      voice_message: cand.voice_message || '',
    });
    setIsCandidateModalOpen(true);
  };

  // Open Add Modal
  const openAddCandidateModal = () => {
    setEditingCandidate(null);
    const nextSerial = candidates.length > 0 ? Math.max(...candidates.map(c => c.serial_number || 0)) + 1 : 1;
    setCandidateFormData({
      serial_number: nextSerial,
      candidate_name: '',
      party_name: '',
      candidate_photo: '',
      party_symbol: '',
      position: 'विधानसभा आमदार',
      section_color: '#ea580c',
      highlighted: false,
      voice_message: '',
    });
    setIsCandidateModalOpen(true);
  };

  // Delete Candidate
  const handleDeleteCandidate = async (id) => {
    if (!window.confirm(isMr ? 'हा उमेदवार EVM बॅलेटवरून काढायचा का?' : 'Delete candidate from EVM ballot?')) return;
    try {
      await workerApi.deleteEvmCandidate(id);
      loadEvmData();
    } catch (err) {
      alert('हटवताना त्रुटी आली.');
    }
  };

  // Toggle Sound Setting
  const toggleSound = async () => {
    const nextVal = !settings.enable_sound;
    setSettings(prev => ({ ...prev, enable_sound: nextVal }));
    try {
      await workerApi.updateEvmSettings({ ...settings, enable_sound: nextVal });
    } catch (e) { }
  };

  // Toggle Vibration Setting
  const toggleVibration = async () => {
    const nextVal = !settings.enable_vibration;
    setSettings(prev => ({ ...prev, enable_vibration: nextVal }));
    try {
      await workerApi.updateEvmSettings({ ...settings, enable_vibration: nextVal });
    } catch (e) { }
  };

  // Save Election Info
  const handleSaveElection = async (e) => {
    e.preventDefault();
    try {
      await workerApi.updateEvmElection(electionFormData);
      await loadEvmData();
      alert(isMr ? 'निवडणूक माहिती जतन झाली!' : 'Election info saved!');
    } catch (err) {
      alert('माहिती जतन करताना त्रुटी आली.');
    }
  };

  // WhatsApp Share Message Generator
  const shareOnWhatsApp = (candidate) => {
    const cand = candidate || votedCandidate || candidates.find(c => c.highlighted) || candidates[0];
    const text = isMr
      ? `🚩 *डेमो EVM मतदान प्रात्यक्षिक*\n\nमी इलेक्ट्रॉनिक मतदान यंत्रावर (EVM) खालील उमेदवाराला मत देऊन प्रात्यक्षिक पूर्ण केले:\n\n👤 *उमेदवार:* ${cand?.candidate_name}\n📌 *अनुक्रमांक:* क्र. ${cand?.serial_number}\n🚩 *पक्ष:* ${cand?.party_name}\n\n🏛️ *${election?.name || 'सांगली विधानसभा निवडणूक २०२६'}*\n\nतुम्ही सुद्धा मतदान प्रात्यक्षिक पाहून जागरूक मतदार बना!`
      : `EVM Voting Demo: Voted for ${cand?.candidate_name} (Serial No. ${cand?.serial_number}, ${cand?.party_name}).`;

    const url = `https://wa.me/?text=${encodeURIComponent(text)}`;
    window.open(url, '_blank');
  };

  // Group candidates into sections/positions
  const sectionGroups = useMemo(() => {
    const map = {};
    candidates.forEach(c => {
      const pos = c.position || (isMr ? 'विधानसभा आमदार' : 'General Ballot');
      if (!map[pos]) {
        map[pos] = {
          position: pos,
          color: c.section_color || '#ea580c',
          candidates: []
        };
      }
      map[pos].candidates.push(c);
    });
    return Object.values(map);
  }, [candidates, isMr]);

  // Memoize full 16 EVM slots per section
  const sectionSlots = useMemo(() => {
    return sectionGroups.map((group, gIdx) => {
      const slots = [];
      const maxSlots = 16;
      for (let i = 1; i <= maxSlots; i++) {
        const match = group.candidates.find(c => Number(c.serial_number) === i);
        slots.push({
          serialNumber: i,
          candidate: match || null
        });
      }
      return { group, gIdx, slots };
    });
  }, [sectionGroups]);

  // Filter allVotes for Admin Candidate-wise Ledger
  const filteredVotes = useMemo(() => {
    if (!allVotes || !Array.isArray(allVotes)) return [];
    if (candidateLedgerFilter === 'all') return allVotes;
    return allVotes.filter(v => String(v.candidate_id) === String(candidateLedgerFilter));
  }, [allVotes, candidateLedgerFilter]);

  return (
    <div className="module-container" style={{ display: 'flex', flexDirection: 'column', gap: '20px', fontFamily: "'Inter', 'Noto Sans Devanagari', 'Mukta', sans-serif" }}>
      {/* Hidden Audio Elements */}
      <audio ref={audioRef} src={voteSongAudio} preload="auto" />
      <audio ref={wrongAudioRef} src={wrongSoundAudio} preload="auto" />

      {/* TOP SAAS COMMAND BAR & MODE SWITCHER */}
      <div style={{
        background: '#ffffff',
        borderRadius: '16px',
        border: '1px solid #e2e8f0',
        padding: '18px 22px',
        boxShadow: '0 4px 16px rgba(15, 23, 42, 0.04)',
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        flexWrap: 'wrap',
        gap: '16px'
      }}>
        <div>
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            <span style={{
              width: '38px',
              height: '38px',
              borderRadius: '10px',
              background: 'linear-gradient(135deg, #0284c7 0%, #0369a1 100%)',
              color: '#ffffff',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              fontSize: '20px',
              boxShadow: '0 4px 10px rgba(2, 132, 199, 0.25)'
            }}>
              🗳️
            </span>
            <div>
              <h1 style={{ fontSize: '19px', fontWeight: '800', color: '#0f172a', margin: 0, lineHeight: 1.2 }}>
                {isMr ? 'प्रत्यक्ष डेमो EVM मतदान प्रणाली' : 'Digital EVM Voting & Training Station'}
              </h1>
              <div style={{ fontSize: '12px', color: '#64748b', marginTop: '2px', display: 'flex', alignItems: 'center', gap: '6px' }}>
                <span>{election?.name || '२८२ - सांगली मतदारसंघ (Sangli Constituency)'}</span>
                <span>•</span>
                <span style={{ color: '#059669', fontWeight: '700' }}>
                  ✓ {isMr ? 'थेट डेटाबेस जोडणी' : 'MySQL Live'}
                </span>
              </div>
            </div>
          </div>
        </div>

        {/* Mode Navigation: Admin Only (Karyakarta is strictly locked to EVM Ballot) */}
        {isAdmin ? (
          <div style={{ display: 'flex', gap: '8px', background: '#f8fafc', padding: '4px', borderRadius: '12px', border: '1px solid #e2e8f0', flexWrap: 'wrap' }}>
            <button
              type="button"
              onClick={() => setActiveMode('evm')}
              style={{
                padding: '8px 14px',
                borderRadius: '9px',
                border: 'none',
                background: currentMode === 'evm' ? '#0f172a' : 'transparent',
                color: currentMode === 'evm' ? '#ffffff' : '#475569',
                fontWeight: '700',
                fontSize: '12.5px',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                gap: '6px',
                transition: 'all 0.15s ease'
              }}
            >
              <Radio size={14} />
              <span>{isMr ? 'EVM मशीन सिम्युलेटर' : 'EVM Simulator'}</span>
            </button>

            <button
              type="button"
              onClick={() => setActiveMode('manage')}
              style={{
                padding: '8px 14px',
                borderRadius: '9px',
                border: 'none',
                background: currentMode === 'manage' ? '#0f172a' : 'transparent',
                color: currentMode === 'manage' ? '#ffffff' : '#475569',
                fontWeight: '700',
                fontSize: '12.5px',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                gap: '6px',
                transition: 'all 0.15s ease'
              }}
            >
              <Users size={14} />
              <span>{isMr ? 'उमेदवार व्यवस्थापन' : 'Manage Candidates'} ({candidates.length})</span>
            </button>

            <button
              type="button"
              onClick={() => setActiveMode('results')}
              style={{
                padding: '8px 14px',
                borderRadius: '9px',
                border: 'none',
                background: currentMode === 'results' ? '#0f172a' : 'transparent',
                color: currentMode === 'results' ? '#ffffff' : '#475569',
                fontWeight: '700',
                fontSize: '12.5px',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                gap: '6px',
                transition: 'all 0.15s ease'
              }}
            >
              <BarChart2 size={14} />
              <span>{isMr ? 'मतदान निकाल' : 'Results & Tally'} ({totalVotes})</span>
            </button>

            <button
              type="button"
              onClick={() => setActiveMode('settings')}
              style={{
                padding: '8px 14px',
                borderRadius: '9px',
                border: 'none',
                background: currentMode === 'settings' ? '#0f172a' : 'transparent',
                color: currentMode === 'settings' ? '#ffffff' : '#475569',
                fontWeight: '700',
                fontSize: '12.5px',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                gap: '6px',
                transition: 'all 0.15s ease'
              }}
            >
              <Settings size={14} />
              <span>{isMr ? 'निवडणूक सेटिंग्ज' : 'Election Settings'}</span>
            </button>
          </div>
        ) : (
          <div style={{
            display: 'flex',
            alignItems: 'center',
            gap: '8px',
            background: '#eff6ff',
            padding: '7px 14px',
            borderRadius: '10px',
            border: '1px solid #bfdbfe'
          }}>
            <span style={{ width: '8px', height: '8px', borderRadius: '50%', background: '#2563eb' }} />
            <span style={{ fontSize: '12.5px', fontWeight: '800', color: '#1d4ed8' }}>
              {isMr ? 'अधिकारी प्रत्यक्ष मतदान केंद्र (EVM)' : 'Officer Polling Station (EVM)'}
            </span>
          </div>
        )}
      </div>

      {/* ELECTION TIMER & ADMIN CONTROL COMMAND CENTER */}
      <div style={{
        background: '#ffffff',
        borderRadius: '16px',
        border: election?.status === 'ACTIVE' && !timeLeft.isExpired ? '2px solid #10b981' : '1px solid #e2e8f0',
        padding: '18px 22px',
        boxShadow: election?.status === 'ACTIVE' && !timeLeft.isExpired
          ? '0 6px 20px rgba(16, 185, 129, 0.12)'
          : '0 4px 14px rgba(15, 23, 42, 0.04)',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
        flexWrap: 'wrap',
        gap: '16px'
      }}>
        {/* Left: Election Live Status & Time Info */}
        <div style={{ display: 'flex', alignItems: 'center', gap: '14px', flexWrap: 'wrap' }}>
          <div style={{
            display: 'inline-flex',
            alignItems: 'center',
            gap: '8px',
            padding: '6px 14px',
            borderRadius: '999px',
            background: election?.status === 'ACTIVE' && !timeLeft.isExpired ? '#dcfce7' : '#fee2e2',
            color: election?.status === 'ACTIVE' && !timeLeft.isExpired ? '#15803d' : '#b91c1c',
            fontWeight: '800',
            fontSize: '12.5px'
          }}>
            <span style={{
              width: '10px',
              height: '10px',
              borderRadius: '50%',
              background: election?.status === 'ACTIVE' && !timeLeft.isExpired ? '#16a34a' : '#dc2626',
              boxShadow: election?.status === 'ACTIVE' && !timeLeft.isExpired ? '0 0 10px #16a34a' : 'none'
            }} />
            <span>
              {election?.status === 'ACTIVE' && !timeLeft.isExpired
                ? (isMr ? '🟢 निवडणूक सुरू आहे (LIVE)' : '🟢 ELECTION LIVE')
                : (isMr ? '🔴 निवडणूक थांबवली आहे (ENDED / STOPPED)' : '🔴 ELECTION CLOSED')}
            </span>
          </div>

          {/* Time Remaining Digital Clocks */}
          {election?.end_time && (
            <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
              <Clock size={16} color="#64748b" />
              <span style={{ fontSize: '12px', fontWeight: '700', color: '#64748b' }}>
                {isMr ? 'उर्वरित वेळ:' : 'Time Left:'}
              </span>

              {timeLeft.isExpired ? (
                <span style={{
                  background: '#fee2e2',
                  color: '#b91c1c',
                  padding: '4px 10px',
                  borderRadius: '6px',
                  fontWeight: '800',
                  fontSize: '12px'
                }}>
                  ⏱️ {isMr ? 'मुदत संपली (Time Expired)' : 'Expired'}
                </span>
              ) : (
                <div style={{ display: 'flex', alignItems: 'center', gap: '4px' }}>
                  {timeLeft.days > 0 && (
                    <div style={{
                      background: '#0f172a',
                      color: '#ffffff',
                      padding: '4px 8px',
                      borderRadius: '6px',
                      fontSize: '12px',
                      fontWeight: '800',
                      letterSpacing: '0.5px'
                    }}>
                      {timeLeft.days} <span style={{ fontSize: '10px', color: '#94a3b8' }}>{isMr ? 'दिवस' : 'd'}</span>
                    </div>
                  )}
                  <div style={{
                    background: '#0f172a',
                    color: '#ffffff',
                    padding: '4px 8px',
                    borderRadius: '6px',
                    fontSize: '12px',
                    fontWeight: '800',
                    letterSpacing: '0.5px'
                  }}>
                    {String(timeLeft.hours).padStart(2, '0')} <span style={{ fontSize: '10px', color: '#94a3b8' }}>{isMr ? 'तास' : 'h'}</span>
                  </div>
                  <span style={{ fontWeight: '800', color: '#0f172a' }}>:</span>
                  <div style={{
                    background: '#0f172a',
                    color: '#ffffff',
                    padding: '4px 8px',
                    borderRadius: '6px',
                    fontSize: '12px',
                    fontWeight: '800',
                    letterSpacing: '0.5px'
                  }}>
                    {String(timeLeft.minutes).padStart(2, '0')} <span style={{ fontSize: '10px', color: '#94a3b8' }}>{isMr ? 'मि.' : 'm'}</span>
                  </div>
                  <span style={{ fontWeight: '800', color: '#0f172a' }}>:</span>
                  <div style={{
                    background: '#2563eb',
                    color: '#ffffff',
                    padding: '4px 8px',
                    borderRadius: '6px',
                    fontSize: '12px',
                    fontWeight: '800',
                    letterSpacing: '0.5px'
                  }}>
                    {String(timeLeft.seconds).padStart(2, '0')} <span style={{ fontSize: '10px', color: '#bfdbfe' }}>{isMr ? 'से.' : 's'}</span>
                  </div>
                </div>
              )}
            </div>
          )}
        </div>

        {/* Right: Admin Action Buttons (Start Election / End Election) */}
        {isAdmin && (
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
            {election?.status === 'ACTIVE' && !timeLeft.isExpired ? (
              <button
                type="button"
                onClick={handleEndElection}
                disabled={isEndingElection}
                style={{
                  background: 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
                  color: '#ffffff',
                  border: 'none',
                  padding: '9px 18px',
                  borderRadius: '10px',
                  fontSize: '13px',
                  fontWeight: '800',
                  cursor: isEndingElection ? 'not-allowed' : 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '8px',
                  boxShadow: '0 4px 12px rgba(220, 38, 38, 0.25)',
                  transition: 'transform 0.1s ease'
                }}
              >
                <Square size={15} />
                <span>{isEndingElection ? (isMr ? 'थांबवत आहे...' : 'Ending...') : (isMr ? '⏹ निवडणूक संपवा / थांबवा' : 'End Election')}</span>
              </button>
            ) : (
              <button
                type="button"
                onClick={() => setIsStartElectionModalOpen(true)}
                style={{
                  background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                  color: '#ffffff',
                  border: 'none',
                  padding: '9px 18px',
                  borderRadius: '10px',
                  fontSize: '13px',
                  fontWeight: '800',
                  cursor: 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '8px',
                  boxShadow: '0 4px 12px rgba(16, 185, 129, 0.25)',
                  transition: 'transform 0.1s ease'
                }}
              >
                <Play size={15} />
                <span>{isMr ? '▶ निवडणूक सुरू करा (Start Timer)' : 'Start Election with Timer'}</span>
              </button>
            )}
          </div>
        )}
      </div>

      {/* ------------------------------------------------------------- */}
      {/* MODE 1: EVM BALLOT UNIT SIMULATOR                            */}
      {/* ------------------------------------------------------------- */}
      {currentMode === 'evm' && (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '20px' }}>

          {/* WORKER ALERT 2: ELECTION IS ACTIVE & CAN VOTE */}
          {election?.status === 'ACTIVE' && !timeLeft.isExpired && !hasVoted && (
            <div style={{
              background: '#ecfdf5',
              border: '1.5px solid #10b981',
              borderRadius: '14px',
              padding: '14px 18px',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between',
              gap: '14px',
              flexWrap: 'wrap',
              boxShadow: '0 4px 12px rgba(16, 185, 129, 0.1)'
            }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                <span style={{ fontSize: '24px' }}>📢</span>
                <div>
                  <div style={{ fontWeight: '800', color: '#065f46', fontSize: '14.5px' }}>
                    {isMr ? 'निवडणूक मतदान सुरू झाले आहे!' : 'Election Voting is Now Live!'}
                  </div>
                  <div style={{ fontSize: '12.5px', color: '#047857', marginTop: '2px' }}>
                    {isMr
                      ? 'कृपया खालील यादीतील आपल्या पसंतीच्या उमेदवारासमोरील निळे बटण दाबून आपले मत नोंदवा. (एका फेरीमध्ये फक्त एकदाच मत देता येईल)'
                      : 'Please press the blue button next to your candidate to vote. (Only one vote allowed per worker).'}
                  </div>
                </div>
              </div>
              <div style={{
                background: '#10b981',
                color: '#ffffff',
                padding: '6px 12px',
                borderRadius: '8px',
                fontSize: '12px',
                fontWeight: '800',
                display: 'flex',
                alignItems: 'center',
                gap: '6px'
              }}>
                <Clock size={14} />
                <span>{isMr ? 'मतदान चालू आहे' : 'Voting in Progress'}</span>
              </div>
            </div>
          )}

          {/* WORKER ALERT 3: ELECTION CLOSED / EXPIRED (AND NOT VOTED) */}
          {(election?.status !== 'ACTIVE' || timeLeft.isExpired) && !hasVoted && (
            <div style={{
              background: '#fef2f2',
              border: '1.5px solid #f87171',
              borderRadius: '14px',
              padding: '14px 18px',
              display: 'flex',
              alignItems: 'center',
              gap: '14px',
              boxShadow: '0 4px 12px rgba(239, 68, 68, 0.08)'
            }}>
              <span style={{ fontSize: '24px' }}>🔒</span>
              <div>
                <div style={{ fontWeight: '800', color: '#991b1b', fontSize: '14.5px' }}>
                  {isMr ? 'सध्या मतदान बंद आहे (Election Paused / Closed)' : 'Voting is Currently Closed'}
                </div>
                <div style={{ fontSize: '12.5px', color: '#b91c1c', marginTop: '2px' }}>
                  {isMr
                    ? 'ॲडमिनने टाइमर लावून निवडणूक सुरू केल्यानंतरच येथे मतदान करता येईल.'
                    : 'Voting will be enabled once the admin starts the election with the timer.'}
                </div>
              </div>
            </div>
          )}

          {/* EVM Ballot Units by Position/Section */}
          {sectionSlots.map(({ group, gIdx, slots }) => {

            return (
              <div
                key={gIdx}
                style={{
                  background: '#f8fafc',
                  border: '5px solid #cbd5e1',
                  borderRadius: '20px',
                  boxShadow: '0 12px 30px rgba(15, 23, 42, 0.08)',
                  overflow: 'hidden'
                }}
              >
                {/* Metallic Ballot Unit Top Casing */}
                <div style={{
                  background: `linear-gradient(90deg, ${group.color} 0%, #1e293b 100%)`,
                  color: '#ffffff',
                  padding: '14px 20px',
                  display: 'flex',
                  justifyContent: 'space-between',
                  alignItems: 'center',
                  borderBottom: '3px solid rgba(0, 0, 0, 0.15)'
                }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                    <span style={{ fontSize: '18px' }}>🇮🇳</span>
                    <div>
                      <h2 style={{ fontSize: '15px', fontWeight: '900', margin: 0, letterSpacing: '0.5px', textTransform: 'uppercase' }}>
                        {group.position} (BALLOT UNIT {gIdx + 1})
                      </h2>
                      <span style={{ fontSize: '11px', color: '#e2e8f0' }}>
                        भारत निवडणूक आयोग • इलेक्ट्रॉनिक मतदान यंत्र
                      </span>
                    </div>
                  </div>

                  <div style={{
                    fontSize: '11.5px',
                    fontWeight: '800',
                    background: 'rgba(255, 255, 255, 0.2)',
                    padding: '4px 10px',
                    borderRadius: '20px'
                  }}>
                    {group.candidates.length} {isMr ? 'अधिकृत उमेदवार' : 'Candidates'}
                  </div>
                </div>

                {/* Ballot Unit Table */}
                <div style={{ overflowX: 'auto', background: '#ffffff' }}>
                  <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left' }}>
                    <thead>
                      <tr style={{ background: '#f1f5f9', borderBottom: '2px solid #cbd5e1', color: '#334155', fontSize: '12px', fontWeight: '800' }}>
                        <th style={{ padding: '12px 14px', width: '60px', textAlign: 'center', borderRight: '1px solid #e2e8f0' }}>
                          {isMr ? 'अनु. क्र.' : 'No.'}
                        </th>
                        <th style={{ padding: '12px 16px', borderRight: '1px solid #e2e8f0' }}>
                          {isMr ? 'उमेदवाराचे नाव व पक्ष' : 'Candidate Name & Party'}
                        </th>
                        <th style={{ padding: '12px 14px', width: '90px', textAlign: 'center', borderRight: '1px solid #e2e8f0' }}>
                          {isMr ? 'फोटो' : 'Photo'}
                        </th>
                        <th style={{ padding: '12px 14px', width: '90px', textAlign: 'center', borderRight: '1px solid #e2e8f0' }}>
                          {isMr ? 'निवडणूक चिन्ह' : 'Symbol'}
                        </th>
                        <th style={{ padding: '12px 16px', width: '170px', textAlign: 'center' }}>
                          {isMr ? 'मतदान बटण (निळे बटण दाबा)' : 'Press Blue Button'}
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      {slots.map(({ serialNumber, candidate }) => {
                        const isVotedThis = votedCandidateId === candidate?.id;
                        const isHighlighted = candidate?.highlighted;

                        return (
                          <tr
                            key={serialNumber}
                            style={{
                              borderBottom: '1px solid #e2e8f0',
                              background: isHighlighted ? '#fff7ed' : isVotedThis ? '#eff6ff' : '#ffffff',
                              transition: 'background 0.2s'
                            }}
                          >
                            {/* Serial Number */}
                            <td style={{
                              padding: '12px 14px',
                              textAlign: 'center',
                              fontWeight: '900',
                              fontSize: '16px',
                              color: '#0f172a',
                              borderRight: '1px solid #e2e8f0'
                            }}>
                              <span style={{
                                width: '32px',
                                height: '32px',
                                borderRadius: '8px',
                                background: isHighlighted ? '#ea580c' : '#f1f5f9',
                                color: isHighlighted ? '#ffffff' : '#1e293b',
                                display: 'inline-flex',
                                alignItems: 'center',
                                justifyContent: 'center'
                              }}>
                                {serialNumber}
                              </span>
                            </td>

                            {/* Candidate Name & Party */}
                            <td style={{ padding: '12px 16px', borderRight: '1px solid #e2e8f0' }}>
                              {candidate ? (
                                <div>
                                  <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                                    <span style={{ fontSize: '15px', fontWeight: '800', color: isHighlighted ? '#c2410c' : '#0f172a' }}>
                                      {candidate.candidate_name}
                                    </span>
                                    {isHighlighted && (
                                      <span style={{
                                        fontSize: '10px',
                                        fontWeight: '800',
                                        background: '#ea580c',
                                        color: '#ffffff',
                                        padding: '2px 8px',
                                        borderRadius: '10px',
                                        display: 'inline-flex',
                                        alignItems: 'center',
                                        gap: '3px'
                                      }}>
                                        <Sparkles size={11} /> {isMr ? 'प्रचार उमेदवार' : 'Key Candidate'}
                                      </span>
                                    )}
                                  </div>
                                  <div style={{ fontSize: '12px', color: '#64748b', fontWeight: '600', marginTop: '2px' }}>
                                    {candidate.party_name}
                                  </div>
                                </div>
                              ) : (
                                <div style={{ height: '38px', color: '#cbd5e1', fontSize: '12px', display: 'flex', alignItems: 'center' }}>
                                  — (रिक्त बॅलेट जागा / Empty Row) —
                                </div>
                              )}
                            </td>

                            {/* Photo */}
                            <td style={{ padding: '8px 14px', textAlign: 'center', borderRight: '1px solid #e2e8f0' }}>
                              <EvmMediaImage
                                src={candidate?.candidate_photo}
                                alt={candidate?.candidate_name || 'Candidate'}
                                fallbackChar={candidate ? candidate.candidate_name?.charAt(0) : ''}
                                style={{
                                  width: '46px',
                                  height: '46px',
                                  borderRadius: '8px',
                                  objectFit: 'cover',
                                  border: isHighlighted ? '2px solid #ea580c' : '1px solid #cbd5e1'
                                }}
                              />
                            </td>

                            {/* Symbol */}
                            <td style={{ padding: '8px 14px', textAlign: 'center', borderRight: '1px solid #e2e8f0' }}>
                              {candidate ? (
                                <EvmMediaImage
                                  src={candidate?.party_symbol}
                                  alt={candidate?.party_name || 'Symbol'}
                                  isSymbol={true}
                                  style={{
                                    width: '44px',
                                    height: '44px',
                                    objectFit: 'contain',
                                    borderRadius: '6px',
                                    padding: '2px',
                                    background: '#ffffff',
                                    border: '1px solid #e2e8f0'
                                  }}
                                />
                              ) : null}
                            </td>

                            {/* Vote Press Button & LED Lamp Column */}
                            <td style={{ padding: '10px 16px', textAlign: 'center' }}>
                              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '14px' }}>
                                {/* Red LED Lamp with Real EVM Glow */}
                                <div
                                  style={{
                                    width: '16px',
                                    height: '16px',
                                    borderRadius: '50%',
                                    background: isVotedThis ? '#ef4444' : '#64748b',
                                    border: '2px solid #334155',
                                    boxShadow: isVotedThis ? '0 0 16px 4px #ef4444, inset 0 0 4px #ffffff' : 'none',
                                    transition: 'all 0.15s ease'
                                  }}
                                  title="EVM Red Vote Lamp"
                                />

                                {/* Realistic 3D Blue EVM Vote Button */}
                                <button
                                  type="button"
                                  onClick={() => handleVotePress(candidate)}
                                  disabled={!candidate || election?.status !== 'ACTIVE' || timeLeft.isExpired || (!isAdmin && hasVoted)}
                                  style={{
                                    width: '100px',
                                    height: '38px',
                                    borderRadius: '8px',
                                    border: 'none',
                                    background: !candidate
                                      ? '#cbd5e1'
                                      : isVotedThis
                                        ? 'linear-gradient(180deg, #16a34a 0%, #15803d 100%)'
                                        : (!isAdmin && hasVoted) || election?.status !== 'ACTIVE' || timeLeft.isExpired
                                          ? '#94a3b8'
                                          : 'linear-gradient(180deg, #2563eb 0%, #1d4ed8 70%, #1e40af 100%)',
                                    color: '#ffffff',
                                    fontWeight: '800',
                                    fontSize: '11.5px',
                                    cursor: !candidate || election?.status !== 'ACTIVE' || timeLeft.isExpired || (!isAdmin && hasVoted) ? 'not-allowed' : 'pointer',
                                    boxShadow: !candidate || (!isAdmin && hasVoted) || election?.status !== 'ACTIVE' || timeLeft.isExpired
                                      ? 'none'
                                      : isVotedThis
                                        ? '0 2px 0 #166534'
                                        : '0 4px 0 #1e3a8a, 0 6px 8px rgba(0, 0, 0, 0.25)',
                                    transform: isVotedThis ? 'translateY(3px)' : 'none',
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'center',
                                    gap: '5px',
                                    letterSpacing: '0.3px',
                                    transition: 'all 0.1s ease',
                                    userSelect: 'none',
                                    opacity: (!candidate || ((!isAdmin && hasVoted) || election?.status !== 'ACTIVE' || timeLeft.isExpired)) && !isVotedThis ? 0.8 : 1
                                  }}
                                  onMouseDown={(e) => {
                                    if (candidate && (isAdmin || !hasVoted) && election?.status === 'ACTIVE' && !timeLeft.isExpired) {
                                      e.currentTarget.style.transform = 'translateY(3px)';
                                      e.currentTarget.style.boxShadow = '0 1px 0 #1e3a8a';
                                    }
                                  }}
                                  onMouseUp={(e) => {
                                    if (candidate && (isAdmin || !hasVoted) && election?.status === 'ACTIVE' && !timeLeft.isExpired) {
                                      e.currentTarget.style.transform = 'none';
                                      e.currentTarget.style.boxShadow = '0 4px 0 #1e3a8a, 0 6px 8px rgba(0, 0, 0, 0.25)';
                                    }
                                  }}
                                >
                                  {isVotedThis ? (
                                    <>
                                      <span>✓</span>
                                      <span>{isMr ? 'मत दिले' : 'VOTED'}</span>
                                    </>
                                  ) : !isAdmin && hasVoted ? (
                                    <>
                                      <span>🔒</span>
                                      <span>{isMr ? 'मत दिले आहे' : 'VOTED'}</span>
                                    </>
                                  ) : election?.status !== 'ACTIVE' || timeLeft.isExpired ? (
                                    <>
                                      <span>🔒</span>
                                      <span>{isMr ? 'मतदान बंद' : 'CLOSED'}</span>
                                    </>
                                  ) : (
                                    <>
                                      <span>🔘</span>
                                      <span>{isMr ? 'बटन दाबा' : 'VOTE'}</span>
                                    </>
                                  )}
                                </button>
                              </div>
                            </td>
                          </tr>
                        );
                      })}
                    </tbody>
                  </table>
                </div>
              </div>
            );
          })}
        </div>
      )}

      {/* ------------------------------------------------------------- */}
      {/* MODE 2: CANDIDATE MANAGEMENT (Add, Edit, Reorder, Delete)     */}
      {/* ------------------------------------------------------------- */}
      {isAdmin && currentMode === 'manage' && (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '20px' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '12px' }}>
            <div>
              <h2 style={{ fontSize: '18px', fontWeight: '800', color: '#0f172a', margin: 0 }}>
                {isMr ? 'EVM बॅलेट उमेदवार व्यवस्थापन' : 'EVM Ballot Candidates Directory'}
              </h2>
              <p style={{ fontSize: '13px', color: '#64748b', margin: '3px 0 0 0' }}>
                {isMr ? 'EVM वरील उमेदवारांचे अनुक्रमांक, फोटो, पक्ष चिन्ह व ऑडिओ संदेश अद्यतनित करा.' : 'Manage candidates on EVM ballot paper with photos, symbols, and audio.'}
              </p>
            </div>

            <button
              type="button"
              onClick={openAddCandidateModal}
              style={{
                background: 'linear-gradient(135deg, #0284c7 0%, #0369a1 100%)',
                color: '#ffffff',
                border: 'none',
                padding: '10px 18px',
                borderRadius: '10px',
                fontSize: '13px',
                fontWeight: '700',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                gap: '8px',
                boxShadow: '0 4px 12px rgba(2, 132, 199, 0.25)'
              }}
            >
              <Plus size={16} />
              <span>{isMr ? '➕ नवीन उमेदवार जोडा' : 'Add Candidate'}</span>
            </button>
          </div>

          {/* Candidates Card Grid */}
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(320px, 1fr))', gap: '16px' }}>
            {candidates.map((cand) => (
              <div
                key={cand.id}
                style={{
                  background: '#ffffff',
                  border: cand.highlighted ? '2px solid #ea580c' : '1px solid #e2e8f0',
                  borderRadius: '14px',
                  padding: '18px',
                  boxShadow: '0 2px 8px rgba(15, 23, 42, 0.04)',
                  display: 'flex',
                  flexDirection: 'column',
                  justifyContent: 'space-between',
                  position: 'relative'
                }}
              >
                {cand.highlighted && (
                  <span style={{
                    position: 'absolute',
                    top: '12px',
                    right: '12px',
                    background: '#ea580c',
                    color: '#ffffff',
                    fontSize: '10px',
                    fontWeight: '800',
                    padding: '2px 8px',
                    borderRadius: '10px'
                  }}>
                    ★ {isMr ? 'प्रचार उमेदवार' : 'Key Candidate'}
                  </span>
                )}

                <div>
                  <div style={{ display: 'flex', alignItems: 'center', gap: '12px', marginBottom: '14px' }}>
                    <span style={{
                      width: '34px',
                      height: '34px',
                      borderRadius: '8px',
                      background: '#f1f5f9',
                      color: '#0f172a',
                      fontWeight: '800',
                      fontSize: '15px',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      flexShrink: 0
                    }}>
                      {cand.serial_number}
                    </span>

                    {cand.candidate_photo && (
                      <EvmMediaImage
                        src={cand.candidate_photo}
                        alt={cand.candidate_name}
                        fallbackChar={cand.candidate_name?.charAt(0)}
                        style={{ width: '48px', height: '48px', borderRadius: '10px', objectFit: 'cover', border: '1px solid #cbd5e1' }}
                      />
                    )}

                    <div style={{ minWidth: 0, flex: 1 }}>
                      <div style={{ fontSize: '15px', fontWeight: '800', color: '#0f172a', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                        {cand.candidate_name}
                      </div>
                      <div style={{ fontSize: '12px', color: '#64748b', fontWeight: '600' }}>
                        {cand.party_name}
                      </div>
                    </div>

                    {cand.party_symbol && (
                      <EvmMediaImage
                        src={cand.party_symbol}
                        alt="symbol"
                        isSymbol={true}
                        style={{ width: '40px', height: '40px', objectFit: 'contain', border: '1px solid #e2e8f0', borderRadius: '8px', padding: '2px' }}
                      />
                    )}
                  </div>

                  <div style={{ background: '#f8fafc', padding: '10px 12px', borderRadius: '8px', fontSize: '12px', color: '#475569', display: 'flex', flexDirection: 'column', gap: '4px' }}>
                    <div>📍 {isMr ? 'पद / विभाग:' : 'Position:'} <strong>{cand.position}</strong></div>
                    <div>🗳️ {isMr ? 'एकूण प्रात्यक्षिक मते:' : 'Demo Votes:'} <strong style={{ color: '#2563eb' }}>{cand.votes || 0}</strong></div>
                    {cand.voice_message && (
                      <div style={{ color: '#059669', fontSize: '11px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                        🗣️ "{cand.voice_message}"
                      </div>
                    )}
                  </div>
                </div>

                <div style={{ display: 'flex', gap: '8px', marginTop: '14px', paddingTop: '12px', borderTop: '1px solid #f1f5f9' }}>
                  <button
                    type="button"
                    onClick={() => openEditCandidateModal(cand)}
                    style={{
                      flex: 1,
                      background: '#eff6ff',
                      color: '#1d4ed8',
                      border: '1px solid #bfdbfe',
                      padding: '7px',
                      borderRadius: '8px',
                      fontSize: '12px',
                      fontWeight: '700',
                      cursor: 'pointer',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      gap: '4px'
                    }}
                  >
                    <Edit2 size={13} />
                    <span>{isMr ? 'संपादित करा' : 'Edit'}</span>
                  </button>

                  <button
                    type="button"
                    onClick={() => handleDeleteCandidate(cand.id)}
                    style={{
                      background: '#fef2f2',
                      color: '#dc2626',
                      border: '1px solid #fecaca',
                      padding: '7px 12px',
                      borderRadius: '8px',
                      fontSize: '12px',
                      fontWeight: '700',
                      cursor: 'pointer',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center'
                    }}
                    title="Delete Candidate"
                  >
                    <Trash2 size={13} />
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* ------------------------------------------------------------- */}
      {/* MODE 3: LIVE RESULTS & TALLY                                 */}
      {/* ------------------------------------------------------------- */}
      {isAdmin && currentMode === 'results' && (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '20px' }}>
          <div style={{
            background: '#ffffff',
            borderRadius: '16px',
            border: '1px solid #e2e8f0',
            padding: '24px',
            boxShadow: '0 4px 16px rgba(15, 23, 42, 0.04)'
          }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px', flexWrap: 'wrap', gap: '12px' }}>
              <div>
                <h2 style={{ fontSize: '18px', fontWeight: '800', color: '#0f172a', margin: 0 }}>
                  📊 {isMr ? 'EVM थेट मतदान निकाल व कल विश्लेषण' : 'EVM Live Mock Polling Results'}
                </h2>
                <p style={{ fontSize: '13px', color: '#64748b', margin: '3px 0 0 0' }}>
                  {isMr ? 'अधिकाऱ्याने घेतलेल्या प्रात्यक्षिक मतदानाची थेट आकडेवारी.' : 'Real-time mock polling tallies recorded from voters in the field.'}
                </p>
              </div>

              <div style={{ display: 'flex', gap: '10px' }}>
                <button
                  type="button"
                  onClick={() => loadEvmData()}
                  style={{
                    background: '#f1f5f9',
                    border: '1px solid #cbd5e1',
                    color: '#334155',
                    padding: '8px 14px',
                    borderRadius: '8px',
                    fontSize: '12.5px',
                    fontWeight: '700',
                    cursor: 'pointer'
                  }}
                >
                  🔄 {isMr ? 'रिफ्रेश करा' : 'Refresh'}
                </button>

                <button
                  type="button"
                  onClick={() => shareOnWhatsApp()}
                  style={{
                    background: '#25D366',
                    color: '#ffffff',
                    border: 'none',
                    padding: '8px 16px',
                    borderRadius: '8px',
                    fontSize: '12.5px',
                    fontWeight: '700',
                    cursor: 'pointer',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '6px'
                  }}
                >
                  <Share2 size={14} />
                  <span>{isMr ? 'निकाल WhatsApp वर पाठवा' : 'Share Results'}</span>
                </button>
              </div>
            </div>

            {/* Results Progress Bars */}
            <div style={{ display: 'flex', flexDirection: 'column', gap: '14px' }}>
              {candidates
                .slice()
                .sort((a, b) => (b.votes || 0) - (a.votes || 0))
                .map((cand, idx) => {
                  const voteCount = cand.votes || 0;
                  const pct = totalVotes > 0 ? Math.round((voteCount / totalVotes) * 100) : 0;
                  const isLeader = idx === 0 && voteCount > 0;

                  return (
                    <div
                      key={cand.id}
                      style={{
                        background: isLeader ? '#f0fdf4' : '#f8fafc',
                        border: isLeader ? '1.5px solid #86efac' : '1px solid #e2e8f0',
                        borderRadius: '12px',
                        padding: '14px 16px'
                      }}
                    >
                      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                          <span style={{
                            width: '26px',
                            height: '26px',
                            borderRadius: '6px',
                            background: isLeader ? '#10b981' : '#cbd5e1',
                            color: isLeader ? '#ffffff' : '#1e293b',
                            fontWeight: '800',
                            fontSize: '12px',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center'
                          }}>
                            {cand.serial_number}
                          </span>

                          <span style={{ fontSize: '14px', fontWeight: '800', color: '#0f172a' }}>
                            {cand.candidate_name}
                          </span>

                          <span style={{ fontSize: '12px', color: '#64748b' }}>
                            ({cand.party_name})
                          </span>

                          {cand.highlighted && (
                            <span style={{ fontSize: '10px', background: '#ea580c', color: '#ffffff', padding: '1px 6px', borderRadius: '8px', fontWeight: '700' }}>
                              ★ {isMr ? 'आपला उमेदवार' : 'Our Candidate'}
                            </span>
                          )}
                        </div>

                        <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                          <span style={{ fontSize: '15px', fontWeight: '900', color: '#0f172a' }}>
                            {voteCount} {isMr ? 'मते' : 'votes'}
                          </span>
                          <span style={{
                            fontSize: '12px',
                            fontWeight: '800',
                            padding: '2px 8px',
                            borderRadius: '10px',
                            background: isLeader ? '#dcfce7' : '#e2e8f0',
                            color: isLeader ? '#15803d' : '#334155'
                          }}>
                            {pct}%
                          </span>
                        </div>
                      </div>

                      {/* Bar Fill */}
                      <div style={{ width: '100%', height: '8px', background: '#e2e8f0', borderRadius: '4px', overflow: 'hidden' }}>
                        <div style={{
                          width: `${pct}%`,
                          height: '100%',
                          background: isLeader ? 'linear-gradient(90deg, #10b981 0%, #059669 100%)' : '#3b82f6',
                          borderRadius: '4px',
                          transition: 'width 0.3s ease'
                        }} />
                      </div>
                    </div>
                  );
                })}
            </div>

            {/* CANDIDATE-WISE VOTER LEDGER (ADMIN AUDIT TABLE) */}
            <div style={{
              marginTop: '34px',
              paddingTop: '24px',
              borderTop: '2px solid #e2e8f0'
            }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '14px', marginBottom: '16px' }}>
                <div>
                  <h3 style={{ fontSize: '16px', fontWeight: '800', color: '#0f172a', margin: 0, display: 'flex', alignItems: 'center', gap: '8px' }}>
                    <span>👥</span>
                    <span>{isMr ? 'उमेदवारांनुसार मतदारांची सविस्तर यादी (Candidate-wise Voter Ledger)' : 'Candidate-wise Voter Ledger'}</span>
                    <span style={{ fontSize: '12px', background: '#dbeafe', color: '#1d4ed8', padding: '2px 8px', borderRadius: '10px' }}>
                      {filteredVotes.length} {isMr ? 'मते' : 'votes'}
                    </span>
                  </h3>
                  <p style={{ fontSize: '12.5px', color: '#64748b', margin: '3px 0 0 0' }}>
                    {isMr ? 'कोणत्या अधिकाऱ्याने / मतदाराने कोणत्या उमेदवाराला मत दिले याची संपूर्ण माहिती.' : 'Audit report showing all candidates and the individual voters who cast votes for them.'}
                  </p>
                </div>

                {/* Candidate Filter Dropdown */}
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <Filter size={15} color="#64748b" />
                  <select
                    value={candidateLedgerFilter}
                    onChange={(e) => setCandidateLedgerFilter(e.target.value)}
                    style={{
                      padding: '7px 12px',
                      borderRadius: '8px',
                      border: '1px solid #cbd5e1',
                      fontSize: '12.5px',
                      fontWeight: '700',
                      color: '#0f172a',
                      background: '#ffffff',
                      cursor: 'pointer'
                    }}
                  >
                    <option value="all">
                      {isMr ? `सर्व उमेदवार (${allVotes.length} मते)` : `All Candidates (${allVotes.length} votes)`}
                    </option>
                    {candidates.map(c => {
                      const count = allVotes.filter(v => String(v.candidate_id) === String(c.id)).length;
                      return (
                        <option key={c.id} value={c.id}>
                          #{c.serial_number} {c.candidate_name} ({count} {isMr ? 'मते' : 'votes'})
                        </option>
                      );
                    })}
                  </select>
                </div>
              </div>

              {/* Candidate Quick Filter Chips */}
              <div style={{ display: 'flex', gap: '8px', overflowX: 'auto', paddingBottom: '10px', marginBottom: '14px' }}>
                <button
                  type="button"
                  onClick={() => setCandidateLedgerFilter('all')}
                  style={{
                    padding: '6px 12px',
                    borderRadius: '8px',
                    border: '1px solid',
                    borderColor: candidateLedgerFilter === 'all' ? '#2563eb' : '#cbd5e1',
                    background: candidateLedgerFilter === 'all' ? '#2563eb' : '#f8fafc',
                    color: candidateLedgerFilter === 'all' ? '#ffffff' : '#334155',
                    fontSize: '12px',
                    fontWeight: '700',
                    cursor: 'pointer',
                    whiteSpace: 'nowrap'
                  }}
                >
                  {isMr ? 'सर्व' : 'All'} ({allVotes.length})
                </button>
                {candidates.map(c => {
                  const count = allVotes.filter(v => String(v.candidate_id) === String(c.id)).length;
                  const isSelected = String(candidateLedgerFilter) === String(c.id);
                  return (
                    <button
                      key={c.id}
                      type="button"
                      onClick={() => setCandidateLedgerFilter(String(c.id))}
                      style={{
                        padding: '6px 12px',
                        borderRadius: '8px',
                        border: '1px solid',
                        borderColor: isSelected ? '#2563eb' : '#cbd5e1',
                        background: isSelected ? '#eff6ff' : '#ffffff',
                        color: isSelected ? '#1d4ed8' : '#334155',
                        fontSize: '12px',
                        fontWeight: '700',
                        cursor: 'pointer',
                        whiteSpace: 'nowrap',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '6px'
                      }}
                    >
                      <span>#{c.serial_number} {c.candidate_name}</span>
                      <span style={{
                        padding: '1px 6px',
                        borderRadius: '10px',
                        fontSize: '11px',
                        background: isSelected ? '#2563eb' : '#e2e8f0',
                        color: isSelected ? '#ffffff' : '#475569'
                      }}>
                        {count}
                      </span>
                    </button>
                  );
                })}
              </div>

              {/* Voter Ledger Table */}
              <div style={{ border: '1px solid #e2e8f0', borderRadius: '12px', overflow: 'hidden' }}>
                <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left', fontSize: '12.5px' }}>
                  <thead>
                    <tr style={{ background: '#f8fafc', borderBottom: '1px solid #e2e8f0', color: '#475569', fontWeight: '800' }}>
                      <th style={{ padding: '10px 14px', width: '50px' }}>#</th>
                      <th style={{ padding: '10px 14px' }}>{isMr ? 'मतदार / अधिकारी नाव' : 'Voter / Officer Name'}</th>
                      <th style={{ padding: '10px 14px' }}>{isMr ? 'मोबाईल / भूमिका' : 'Phone / Role'}</th>
                      <th style={{ padding: '10px 14px' }}>{isMr ? 'मतदान केलेला उमेदवार' : 'Candidate Voted'}</th>
                      <th style={{ padding: '10px 14px' }}>{isMr ? 'बूथ / प्रभाग' : 'Booth / Ward'}</th>
                      <th style={{ padding: '10px 14px' }}>{isMr ? 'तारीख व वेळ' : 'Date & Time'}</th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredVotes.length === 0 ? (
                      <tr>
                        <td colSpan={6} style={{ padding: '24px', textAlign: 'center', color: '#64748b' }}>
                          🗳️ {isMr ? 'या निवडीसाठी अद्याप कोणतीही मते नोंदवली गेलेली नाहीत.' : 'No votes found for this selection yet.'}
                        </td>
                      </tr>
                    ) : (
                      filteredVotes.map((v, idx) => (
                        <tr
                          key={v.id || idx}
                          style={{
                            borderBottom: '1px solid #f1f5f9',
                            background: idx % 2 === 0 ? '#ffffff' : '#fafafa'
                          }}
                        >
                          <td style={{ padding: '10px 14px', fontWeight: '700', color: '#64748b' }}>
                            {idx + 1}
                          </td>
                          <td style={{ padding: '10px 14px', fontWeight: '700', color: '#0f172a' }}>
                            <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                              <span style={{
                                width: '26px',
                                height: '26px',
                                borderRadius: '50%',
                                background: '#dbeafe',
                                color: '#1d4ed8',
                                display: 'inline-flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                fontSize: '11px',
                                fontWeight: '800'
                              }}>
                                {(v.voter_name || 'V').charAt(0)}
                              </span>
                              <span>{v.voter_name || (isMr ? 'अधिकारी / मतदार' : 'Officer / Voter')}</span>
                            </div>
                          </td>
                          <td style={{ padding: '10px 14px', color: '#475569' }}>
                            <div>{v.voter_phone || '—'}</div>
                            <span style={{ fontSize: '10.5px', color: '#64748b', textTransform: 'capitalize' }}>
                              {v.voter_role || 'worker'}
                            </span>
                          </td>
                          <td style={{ padding: '10px 14px' }}>
                            <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                              <span style={{
                                padding: '3px 8px',
                                borderRadius: '6px',
                                background: '#fef3c7',
                                color: '#92400e',
                                fontWeight: '800',
                                fontSize: '11.5px'
                              }}>
                                👤 {v.candidate_name || `ID #${v.candidate_id}`}
                              </span>
                              {v.party_name && (
                                <span style={{ fontSize: '11px', color: '#64748b' }}>
                                  ({v.party_name})
                                </span>
                              )}
                            </div>
                          </td>
                          <td style={{ padding: '10px 14px', color: '#475569' }}>
                            {v.booth_number || v.village_ward_name || 'सांगली मध्य'}
                          </td>
                          <td style={{ padding: '10px 14px', color: '#64748b', fontSize: '11.5px' }}>
                            {v.created_at ? new Date(v.created_at).toLocaleString('mr-IN', {
                              dateStyle: 'short',
                              timeStyle: 'medium'
                            }) : '—'}
                          </td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* ------------------------------------------------------------- */}
      {/* MODE 4: ELECTION & SYSTEM SETTINGS                            */}
      {/* ------------------------------------------------------------- */}
      {isAdmin && currentMode === 'settings' && (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '20px' }}>
          <div style={{
            background: '#ffffff',
            borderRadius: '16px',
            border: '1px solid #e2e8f0',
            padding: '24px',
            boxShadow: '0 4px 16px rgba(15, 23, 42, 0.04)'
          }}>
            <h2 style={{ fontSize: '18px', fontWeight: '800', color: '#0f172a', margin: '0 0 16px 0' }}>
              ⚙️ {isMr ? 'निवडणूक माहिती व EVM प्रात्यक्षिक सेटिंग्ज' : 'Election & EVM Simulation Configuration'}
            </h2>

            <form onSubmit={handleSaveElection} style={{ display: 'flex', flexDirection: 'column', gap: '16px', maxWidth: '600px' }}>
              <div>
                <label style={{ display: 'block', fontSize: '13px', fontWeight: '700', color: '#334155', marginBottom: '6px' }}>
                  {isMr ? 'निवडणूक शीर्षक / नाव:' : 'Election Title:'}
                </label>
                <input
                  type="text"
                  value={electionFormData.name}
                  onChange={(e) => setElectionFormData(prev => ({ ...prev, name: e.target.value }))}
                  style={{
                    width: '100%',
                    padding: '10px 12px',
                    borderRadius: '8px',
                    border: '1px solid #cbd5e1',
                    fontSize: '13px',
                    boxSizing: 'border-box'
                  }}
                  placeholder="उदा. २८२ - सांगली विधानसभा निवडणूक २०२६"
                />
              </div>

              <div>
                <label style={{ display: 'block', fontSize: '13px', fontWeight: '700', color: '#334155', marginBottom: '6px' }}>
                  {isMr ? 'मतदार आवाहन संदेश (Banner Message):' : 'Campaign Appeal Message:'}
                </label>
                <textarea
                  rows="2"
                  value={electionFormData.message}
                  onChange={(e) => setElectionFormData(prev => ({ ...prev, message: e.target.value }))}
                  style={{
                    width: '100%',
                    padding: '10px 12px',
                    borderRadius: '8px',
                    border: '1px solid #cbd5e1',
                    fontSize: '13px',
                    boxSizing: 'border-box'
                  }}
                  placeholder="घड्याळ चिन्हासमोरील क्रमांक २ बटण दाबून उमेदवाराला प्रचंड मतांनी विजयी करा!"
                />
              </div>

              <div>
                <label style={{ display: 'block', fontSize: '13px', fontWeight: '700', color: '#334155', marginBottom: '6px' }}>
                  {isMr ? 'मतदान दिनांक व वेळ:' : 'Polling Date & Hours:'}
                </label>
                <input
                  type="text"
                  value={electionFormData.date_time}
                  onChange={(e) => setElectionFormData(prev => ({ ...prev, date_time: e.target.value }))}
                  style={{
                    width: '100%',
                    padding: '10px 12px',
                    borderRadius: '8px',
                    border: '1px solid #cbd5e1',
                    fontSize: '13px',
                    boxSizing: 'border-box'
                  }}
                  placeholder="मतदान दिनांक : २० नोव्हेंबर, २०२६ रोजी सकाळी ७:०० ते सायंकाळी ६:००"
                />
              </div>

              <div>
                <label style={{ display: 'block', fontSize: '13px', fontWeight: '700', color: '#334155', marginBottom: '6px' }}>
                  {isMr ? 'WhatsApp संपर्क क्रमांक (देश कोड सह):' : 'Campaign WhatsApp Number:'}
                </label>
                <input
                  type="text"
                  value={electionFormData.whatsapp_number}
                  onChange={(e) => setElectionFormData(prev => ({ ...prev, whatsapp_number: e.target.value }))}
                  style={{
                    width: '100%',
                    padding: '10px 12px',
                    borderRadius: '8px',
                    border: '1px solid #cbd5e1',
                    fontSize: '13px',
                    boxSizing: 'border-box'
                  }}
                  placeholder="919822011231"
                />
              </div>

              <button
                type="submit"
                style={{
                  alignSelf: 'flex-start',
                  background: '#2563eb',
                  color: '#ffffff',
                  border: 'none',
                  padding: '10px 22px',
                  borderRadius: '8px',
                  fontWeight: '700',
                  fontSize: '13px',
                  cursor: 'pointer',
                  marginTop: '8px'
                }}
              >
                💾 {isMr ? 'माहिती जतन करा' : 'Save Changes'}
              </button>
            </form>
          </div>
        </div>
      )}

      {/* ------------------------------------------------------------- */}
      {/* ADD / EDIT CANDIDATE MODAL (ADMIN ONLY)                       */}
      {/* ------------------------------------------------------------- */}
      {isAdmin && isCandidateModalOpen && (
        <div style={{
          position: 'fixed',
          top: 0,
          left: 0,
          right: 0,
          bottom: 0,
          background: 'rgba(15, 23, 42, 0.65)',
          backdropFilter: 'blur(4px)',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          zIndex: 1000,
          padding: '16px'
        }}>
          <div style={{
            background: '#ffffff',
            borderRadius: '18px',
            width: '100%',
            maxWidth: '520px',
            maxHeight: '90vh',
            overflowY: 'auto',
            boxShadow: '0 20px 40px rgba(0, 0, 0, 0.25)',
            border: '1px solid #cbd5e1'
          }}>
            <div style={{
              background: '#0f172a',
              color: '#ffffff',
              padding: '16px 20px',
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'center'
            }}>
              <h3 style={{ fontSize: '16px', fontWeight: '800', margin: 0 }}>
                {editingCandidate
                  ? (isMr ? '✏️ उमेदवार माहिती संपादित करा' : 'Edit Candidate')
                  : (isMr ? '➕ EVM बॅलेटवर नवीन उमेदवार जोडा' : 'Add Candidate to EVM')}
              </h3>
              <button
                type="button"
                onClick={() => setIsCandidateModalOpen(false)}
                style={{ background: 'none', border: 'none', color: '#94a3b8', fontSize: '18px', cursor: 'pointer' }}
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleSaveCandidate} style={{ padding: '20px', display: 'flex', flexDirection: 'column', gap: '14px' }}>
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 2fr', gap: '12px' }}>
                <div>
                  <label style={{ display: 'block', fontSize: '12px', fontWeight: '700', color: '#334155', marginBottom: '4px' }}>
                    {isMr ? 'अनुक्रमांक (१-१६):' : 'Serial No (1-16):'}
                  </label>
                  <input
                    type="number"
                    min="1"
                    max="16"
                    value={candidateFormData.serial_number}
                    onChange={(e) => setCandidateFormData(prev => ({ ...prev, serial_number: e.target.value }))}
                    style={{ width: '100%', padding: '9px', borderRadius: '7px', border: '1px solid #cbd5e1', fontSize: '13px', boxSizing: 'border-box' }}
                    required
                  />
                </div>

                <div>
                  <label style={{ display: 'block', fontSize: '12px', fontWeight: '700', color: '#334155', marginBottom: '4px' }}>
                    {isMr ? 'उमेदवाराचे पूर्ण नाव:' : 'Candidate Full Name:'}
                  </label>
                  <input
                    type="text"
                    value={candidateFormData.candidate_name}
                    onChange={(e) => setCandidateFormData(prev => ({ ...prev, candidate_name: e.target.value }))}
                    style={{ width: '100%', padding: '9px', borderRadius: '7px', border: '1px solid #cbd5e1', fontSize: '13px', boxSizing: 'border-box' }}
                    placeholder="उदा. सौ. वंदना चंद्रकांत परब"
                    required
                  />
                </div>
              </div>

              <div>
                <label style={{ display: 'block', fontSize: '12px', fontWeight: '700', color: '#334155', marginBottom: '4px' }}>
                  {isMr ? 'पक्षाचे नाव:' : 'Party Name:'}
                </label>
                <input
                  type="text"
                  value={candidateFormData.party_name}
                  onChange={(e) => setCandidateFormData(prev => ({ ...prev, party_name: e.target.value }))}
                  style={{ width: '100%', padding: '9px', borderRadius: '7px', border: '1px solid #cbd5e1', fontSize: '13px', boxSizing: 'border-box' }}
                  placeholder="उदा. राष्ट्रवादी काँग्रेस पक्ष (घड्याळ)"
                  required
                />
              </div>

              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px' }}>
                <div>
                  <label style={{ display: 'block', fontSize: '12px', fontWeight: '700', color: '#334155', marginBottom: '4px' }}>
                    {isMr ? 'फोटो URL / Web Link:' : 'Photo URL:'}
                  </label>
                  <input
                    type="text"
                    value={candidateFormData.candidate_photo}
                    onChange={(e) => setCandidateFormData(prev => ({ ...prev, candidate_photo: e.target.value }))}
                    style={{ width: '100%', padding: '9px', borderRadius: '7px', border: '1px solid #cbd5e1', fontSize: '13px', boxSizing: 'border-box' }}
                    placeholder="https://..."
                  />
                </div>

                <div>
                  <label style={{ display: 'block', fontSize: '12px', fontWeight: '700', color: '#334155', marginBottom: '4px' }}>
                    {isMr ? 'निवडणूक चिन्ह URL:' : 'Symbol URL:'}
                  </label>
                  <input
                    type="text"
                    value={candidateFormData.party_symbol}
                    onChange={(e) => setCandidateFormData(prev => ({ ...prev, party_symbol: e.target.value }))}
                    style={{ width: '100%', padding: '9px', borderRadius: '7px', border: '1px solid #cbd5e1', fontSize: '13px', boxSizing: 'border-box' }}
                    placeholder="https://..."
                  />
                </div>
              </div>

              <div>
                <label style={{ display: 'block', fontSize: '12px', fontWeight: '700', color: '#334155', marginBottom: '4px' }}>
                  {isMr ? 'निवडणूक पद / जागा:' : 'Electoral Position / Seat:'}
                </label>
                <input
                  type="text"
                  value={candidateFormData.position}
                  onChange={(e) => setCandidateFormData(prev => ({ ...prev, position: e.target.value }))}
                  style={{ width: '100%', padding: '9px', borderRadius: '7px', border: '1px solid #cbd5e1', fontSize: '13px', boxSizing: 'border-box' }}
                  placeholder="उदा. विधानसभा आमदार / नगरसेवक प्रभाग १"
                />
              </div>

              <div>
                <label style={{ display: 'block', fontSize: '12px', fontWeight: '700', color: '#334155', marginBottom: '4px' }}>
                  {isMr ? 'ऑडिओ / मतदारांसाठी आभार संदेश:' : 'Voice Audio Thank You Message:'}
                </label>
                <input
                  type="text"
                  value={candidateFormData.voice_message}
                  onChange={(e) => setCandidateFormData(prev => ({ ...prev, voice_message: e.target.value }))}
                  style={{ width: '100%', padding: '9px', borderRadius: '7px', border: '1px solid #cbd5e1', fontSize: '13px', boxSizing: 'border-box' }}
                  placeholder="घड्याळ चिन्हासमोरील बटण दाबल्याबद्दल मनापासून धन्यवाद!"
                />
              </div>

              <div style={{ display: 'flex', alignItems: 'center', gap: '8px', padding: '6px 0' }}>
                <input
                  type="checkbox"
                  id="chkHighlighted"
                  checked={candidateFormData.highlighted}
                  onChange={(e) => setCandidateFormData(prev => ({ ...prev, highlighted: e.target.checked }))}
                  style={{ width: '16px', height: '16px', cursor: 'pointer' }}
                />
                <label htmlFor="chkHighlighted" style={{ fontSize: '13px', fontWeight: '700', color: '#0f172a', cursor: 'pointer' }}>
                  ★ {isMr ? 'हा आपला मुख्य प्रचार उमेदवार म्हणून हायलाइट करा (Orange Highlight)' : 'Highlight as Key Campaign Candidate'}
                </label>
              </div>

              <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '10px', marginTop: '10px', paddingTop: '12px', borderTop: '1px solid #e2e8f0' }}>
                <button
                  type="button"
                  onClick={() => setIsCandidateModalOpen(false)}
                  style={{ padding: '8px 16px', borderRadius: '8px', border: '1px solid #cbd5e1', background: '#f8fafc', color: '#475569', fontWeight: '700', cursor: 'pointer' }}
                >
                  {isMr ? 'रद्द करा' : 'Cancel'}
                </button>

                <button
                  type="submit"
                  disabled={isSavingCandidate}
                  style={{
                    padding: '8px 22px',
                    borderRadius: '8px',
                    border: 'none',
                    background: '#2563eb',
                    color: '#ffffff',
                    fontWeight: '800',
                    cursor: isSavingCandidate ? 'not-allowed' : 'pointer'
                  }}
                >
                  {isSavingCandidate ? 'जतन होत आहे...' : (isMr ? 'जतन करा' : 'Save')}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ------------------------------------------------------------- */}
      {/* MODAL: ADMIN START ELECTION WITH TIMER                        */}
      {/* ------------------------------------------------------------- */}
      {isStartElectionModalOpen && (
        <div style={{
          position: 'fixed',
          inset: 0,
          background: 'rgba(15, 23, 42, 0.65)',
          backdropFilter: 'blur(4px)',
          zIndex: 1000,
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          padding: '16px'
        }}>
          <div style={{
            background: '#ffffff',
            borderRadius: '16px',
            maxWidth: '520px',
            width: '100%',
            padding: '24px',
            boxShadow: '0 20px 40px rgba(0, 0, 0, 0.25)',
            position: 'relative'
          }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                <span style={{
                  width: '36px',
                  height: '36px',
                  borderRadius: '10px',
                  background: '#dcfce7',
                  color: '#16a34a',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  fontSize: '18px'
                }}>
                  ⏱️
                </span>
                <div>
                  <h3 style={{ fontSize: '17px', fontWeight: '800', color: '#0f172a', margin: 0 }}>
                    {isMr ? 'निवडणूक सुरू करा व टाइमर लावा' : 'Start Election with Timer'}
                  </h3>
                  <span style={{ fontSize: '12px', color: '#64748b' }}>
                    {isMr ? 'कालावधी संपताच मतदान आपोआप थांबवले जाईल' : 'Voting will automatically freeze when the timer ends'}
                  </span>
                </div>
              </div>
              <button
                type="button"
                onClick={() => setIsStartElectionModalOpen(false)}
                style={{ background: 'none', border: 'none', fontSize: '20px', color: '#94a3b8', cursor: 'pointer' }}
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleStartElection}>
              {/* Election Title Input */}
              <div style={{ marginBottom: '14px' }}>
                <label style={{ display: 'block', fontSize: '13px', fontWeight: '700', color: '#334155', marginBottom: '6px' }}>
                  {isMr ? 'निवडणुकीचे नाव / शीर्षक (Election Title):' : 'Election Title / Name:'}
                </label>
                <input
                  type="text"
                  value={timerFormData.name}
                  onChange={(e) => setTimerFormData(prev => ({ ...prev, name: e.target.value }))}
                  placeholder="उदा. २८२ - सांगली विधानसभा निवडणूक २०२६"
                  style={{
                    width: '100%',
                    padding: '10px 12px',
                    borderRadius: '8px',
                    border: '1.5px solid #cbd5e1',
                    fontSize: '13.5px',
                    fontWeight: '700',
                    color: '#0f172a',
                    boxSizing: 'border-box'
                  }}
                  required
                />
              </div>

              {/* Timer Duration Inputs (Day, Hr, Min, Sec) */}
              <div style={{ marginBottom: '16px' }}>
                <label style={{ display: 'block', fontSize: '13px', fontWeight: '700', color: '#334155', marginBottom: '8px' }}>
                  {isMr ? 'मतदान कालावधी निश्चित करा:' : 'Set Voting Duration:'}
                </label>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: '10px' }}>
                  {/* Days */}
                  <div>
                    <label style={{ display: 'block', fontSize: '11px', fontWeight: '700', color: '#64748b', marginBottom: '4px', textAlign: 'center' }}>
                      {isMr ? 'दिवस (Days)' : 'Days'}
                    </label>
                    <input
                      type="number"
                      min="0"
                      max="30"
                      value={timerFormData.days}
                      onChange={(e) => setTimerFormData(prev => ({ ...prev, days: e.target.value }))}
                      style={{
                        width: '100%',
                        padding: '10px 8px',
                        textAlign: 'center',
                        borderRadius: '8px',
                        border: '1.5px solid #cbd5e1',
                        fontSize: '15px',
                        fontWeight: '800',
                        color: '#0f172a',
                        boxSizing: 'border-box'
                      }}
                    />
                  </div>

                  {/* Hours */}
                  <div>
                    <label style={{ display: 'block', fontSize: '11px', fontWeight: '700', color: '#64748b', marginBottom: '4px', textAlign: 'center' }}>
                      {isMr ? 'तास (Hours)' : 'Hours'}
                    </label>
                    <input
                      type="number"
                      min="0"
                      max="23"
                      value={timerFormData.hours}
                      onChange={(e) => setTimerFormData(prev => ({ ...prev, hours: e.target.value }))}
                      style={{
                        width: '100%',
                        padding: '10px 8px',
                        textAlign: 'center',
                        borderRadius: '8px',
                        border: '1.5px solid #cbd5e1',
                        fontSize: '15px',
                        fontWeight: '800',
                        color: '#0f172a',
                        boxSizing: 'border-box'
                      }}
                    />
                  </div>

                  {/* Minutes */}
                  <div>
                    <label style={{ display: 'block', fontSize: '11px', fontWeight: '700', color: '#64748b', marginBottom: '4px', textAlign: 'center' }}>
                      {isMr ? 'मि. (Minutes)' : 'Minutes'}
                    </label>
                    <input
                      type="number"
                      min="0"
                      max="59"
                      value={timerFormData.minutes}
                      onChange={(e) => setTimerFormData(prev => ({ ...prev, minutes: e.target.value }))}
                      style={{
                        width: '100%',
                        padding: '10px 8px',
                        textAlign: 'center',
                        borderRadius: '8px',
                        border: '1.5px solid #cbd5e1',
                        fontSize: '15px',
                        fontWeight: '800',
                        color: '#0f172a',
                        boxSizing: 'border-box'
                      }}
                    />
                  </div>

                  {/* Seconds */}
                  <div>
                    <label style={{ display: 'block', fontSize: '11px', fontWeight: '700', color: '#64748b', marginBottom: '4px', textAlign: 'center' }}>
                      {isMr ? 'से. (Seconds)' : 'Seconds'}
                    </label>
                    <input
                      type="number"
                      min="0"
                      max="59"
                      value={timerFormData.seconds}
                      onChange={(e) => setTimerFormData(prev => ({ ...prev, seconds: e.target.value }))}
                      style={{
                        width: '100%',
                        padding: '10px 8px',
                        textAlign: 'center',
                        borderRadius: '8px',
                        border: '1.5px solid #cbd5e1',
                        fontSize: '15px',
                        fontWeight: '800',
                        color: '#0f172a',
                        boxSizing: 'border-box'
                      }}
                    />
                  </div>
                </div>
              </div>

              {/* Quick Preset Buttons */}
              <div style={{ marginBottom: '16px' }}>
                <span style={{ fontSize: '11.5px', color: '#64748b', fontWeight: '700', display: 'block', marginBottom: '6px' }}>
                  ⚡ {isMr ? 'जलद पर्याय (Presets):' : 'Quick Presets:'}
                </span>
                <div style={{ display: 'flex', gap: '6px', flexWrap: 'wrap' }}>
                  {[
                    { label: isMr ? '१५ मि.' : '15m', d: 0, h: 0, m: 15, s: 0 },
                    { label: isMr ? '१ तास' : '1h', d: 0, h: 1, m: 0, s: 0 },
                    { label: isMr ? '४ तास' : '4h', d: 0, h: 4, m: 0, s: 0 },
                    { label: isMr ? '८ तास' : '8h', d: 0, h: 8, m: 0, s: 0 },
                    { label: isMr ? '१ दिवस' : '1d', d: 1, h: 0, m: 0, s: 0 },
                    { label: isMr ? '२ दिवस' : '2d', d: 2, h: 0, m: 0, s: 0 }
                  ].map((p, idx) => (
                    <button
                      key={idx}
                      type="button"
                      onClick={() => setTimerFormData(prev => ({ ...prev, days: p.d, hours: p.h, minutes: p.m, seconds: p.s }))}
                      style={{
                        padding: '5px 10px',
                        borderRadius: '6px',
                        border: '1px solid #cbd5e1',
                        background: '#f8fafc',
                        color: '#334155',
                        fontSize: '11.5px',
                        fontWeight: '700',
                        cursor: 'pointer'
                      }}
                    >
                      {p.label}
                    </button>
                  ))}
                </div>
              </div>

              {/* Reset Previous Votes Checkbox */}
              <div style={{
                background: '#f8fafc',
                border: '1px solid #e2e8f0',
                borderRadius: '10px',
                padding: '12px 14px',
                display: 'flex',
                alignItems: 'center',
                gap: '10px',
                marginBottom: '20px'
              }}>
                <input
                  type="checkbox"
                  id="chkResetVotes"
                  checked={timerFormData.reset_votes}
                  onChange={(e) => setTimerFormData(prev => ({ ...prev, reset_votes: e.target.checked }))}
                  style={{ width: '18px', height: '18px', cursor: 'pointer' }}
                />
                <label htmlFor="chkResetVotes" style={{ fontSize: '12.5px', color: '#1e293b', fontWeight: '700', cursor: 'pointer' }}>
                  🔄 {isMr ? 'नवीन फेरीसाठी मागील सर्व मते रीसेट करा (Reset previous votes)' : 'Reset all previous votes for a fresh election round'}
                </label>
              </div>

              {/* Submit Buttons */}
              <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '10px' }}>
                <button
                  type="button"
                  onClick={() => setIsStartElectionModalOpen(false)}
                  style={{
                    padding: '9px 16px',
                    borderRadius: '8px',
                    border: '1px solid #cbd5e1',
                    background: '#ffffff',
                    color: '#475569',
                    fontWeight: '700',
                    fontSize: '13px',
                    cursor: 'pointer'
                  }}
                >
                  {isMr ? 'रद्द करा' : 'Cancel'}
                </button>

                <button
                  type="submit"
                  disabled={isStartingElection}
                  style={{
                    padding: '9px 22px',
                    borderRadius: '8px',
                    border: 'none',
                    background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                    color: '#ffffff',
                    fontWeight: '800',
                    fontSize: '13px',
                    cursor: isStartingElection ? 'not-allowed' : 'pointer',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '6px',
                    boxShadow: '0 4px 12px rgba(16, 185, 129, 0.25)'
                  }}
                >
                  <Play size={15} />
                  <span>{isStartingElection ? (isMr ? 'सुरू होत आहे...' : 'Starting...') : (isMr ? 'निवडणूक सुरू करा' : 'Start Election Now')}</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ------------------------------------------------------------- */}
      {/* MODAL: SHOW WHOM THE VOTE WAS CAST FOR (कोणत्या उमेदवाराला मत दिले) */}
      {/* ------------------------------------------------------------- */}
      {voteSuccessModalCandidate && (
        <div style={{
          position: 'fixed',
          inset: 0,
          background: 'rgba(15, 23, 42, 0.65)',
          backdropFilter: 'blur(5px)',
          zIndex: 1100,
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          padding: '16px'
        }}>
          <div style={{
            background: '#ffffff',
            borderRadius: '20px',
            maxWidth: '430px',
            width: '100%',
            padding: '24px',
            boxShadow: '0 20px 45px rgba(0, 0, 0, 0.25)',
            textAlign: 'center',
            position: 'relative',
            border: '2px solid #22c55e'
          }}>
            {/* Success Checkmark Circle */}
            <div style={{
              width: '60px',
              height: '60px',
              borderRadius: '50%',
              background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
              color: '#ffffff',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              fontSize: '28px',
              margin: '0 auto 12px auto',
              boxShadow: '0 8px 20px rgba(16, 185, 129, 0.35)'
            }}>
              ✓
            </div>

            <h3 style={{ fontSize: '18px', fontWeight: '900', color: '#0f172a', margin: '0 0 6px 0' }}>
              {isMr ? 'आपले मत यशस्वीरीत्या नोंदवले!' : 'Vote Successfully Recorded!'}
            </h3>
            <p style={{ fontSize: '13px', color: '#64748b', margin: '0 0 18px 0' }}>
              {isMr
                ? 'तुम्ही खालील उमेदवाराला मत दिले आहे:'
                : 'You have voted for the following candidate:'}
            </p>

            {/* Candidate Info Card */}
            <div style={{
              background: '#f8fafc',
              border: '2px solid #cbd5e1',
              borderRadius: '16px',
              padding: '14px',
              display: 'flex',
              alignItems: 'center',
              gap: '12px',
              marginBottom: '18px',
              textAlign: 'left'
            }}>
              {/* Photo with Serial Badge */}
              <div style={{ position: 'relative' }}>
                <EvmMediaImage
                  src={voteSuccessModalCandidate.candidate_photo}
                  alt={voteSuccessModalCandidate.candidate_name}
                  fallbackChar={voteSuccessModalCandidate.candidate_name?.charAt(0)}
                  style={{
                    width: '60px',
                    height: '60px',
                    borderRadius: '12px',
                    objectFit: 'cover',
                    border: '2px solid #2563eb'
                  }}
                />
                <span style={{
                  position: 'absolute',
                  top: '-6px',
                  left: '-6px',
                  background: '#0f172a',
                  color: '#ffffff',
                  fontSize: '11px',
                  fontWeight: '900',
                  padding: '2px 6px',
                  borderRadius: '6px'
                }}>
                  #{voteSuccessModalCandidate.serial_number || '1'}
                </span>
              </div>

              {/* Names */}
              <div style={{ flex: 1 }}>
                <div style={{ fontSize: '15.5px', fontWeight: '900', color: '#0f172a' }}>
                  {voteSuccessModalCandidate.candidate_name}
                </div>
                <div style={{ fontSize: '12.5px', color: '#475569', fontWeight: '700', marginTop: '2px' }}>
                  {voteSuccessModalCandidate.party_name || (isMr ? 'स्वतंत्र' : 'Independent')}
                </div>
                {voteSuccessModalCandidate.position && (
                  <div style={{ fontSize: '11px', color: '#64748b', marginTop: '2px' }}>
                    {voteSuccessModalCandidate.position}
                  </div>
                )}
              </div>

              {/* Symbol */}
              {voteSuccessModalCandidate.party_symbol && (
                <div style={{
                  width: '50px',
                  height: '50px',
                  borderRadius: '10px',
                  background: '#ffffff',
                  border: '1.5px solid #e2e8f0',
                  padding: '3px',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  flexShrink: 0
                }}>
                  <EvmMediaImage
                    src={voteSuccessModalCandidate.party_symbol}
                    alt={voteSuccessModalCandidate.party_name || 'चिन्ह'}
                    isSymbol={true}
                    style={{ width: '100%', height: '100%', objectFit: 'contain' }}
                  />
                </div>
              )}
            </div>

            {/* Locked Info Pill */}
            <div style={{
              background: '#eff6ff',
              borderRadius: '10px',
              padding: '10px 14px',
              fontSize: '12px',
              color: '#1e40af',
              marginBottom: '18px',
              display: 'flex',
              alignItems: 'center',
              gap: '8px',
              textAlign: 'left'
            }}>
              <span>🔒</span>
              <span>
                {isMr
                  ? 'या निवडणुकीसाठी आपले मत सुरक्षित नोंदवले गेले असून ते बदलता येणार नाही.'
                  : 'Your vote is recorded and securely locked for this election.'}
              </span>
            </div>

            {/* Close Button */}
            <button
              type="button"
              onClick={() => setVoteSuccessModalCandidate(null)}
              style={{
                width: '100%',
                padding: '11px',
                borderRadius: '12px',
                border: 'none',
                background: '#0f172a',
                color: '#ffffff',
                fontWeight: '800',
                fontSize: '13.5px',
                cursor: 'pointer'
              }}
            >
              {isMr ? 'समजले / बंद करा' : 'OK / Close'}
            </button>
          </div>
        </div>
      )}
    </div>
  );
}

export default React.memo(KarkartaEvmVotingSystem);
