import apiClient from '../../api/axios';

export const workerApi = {
  // 1. Overview & Stats
  getOverview: async () => {
    const res = await apiClient.get('/worker/overview');
    return res.data?.data;
  },

  // 2. Profile
  getProfile: async () => {
    const res = await apiClient.get('/worker/profile');
    return res.data?.data;
  },
  updateProfile: async (data) => {
    const res = await apiClient.put('/worker/profile', data);
    return res.data;
  },

  // 3. Tasks
  getTasks: async () => {
    const res = await apiClient.get('/worker/tasks');
    return res.data?.data;
  },
  createTask: async (taskData) => {
    const res = await apiClient.post('/worker/tasks', taskData);
    return res.data;
  },
  toggleTask: async (id) => {
    const res = await apiClient.patch(`/worker/tasks/${id}/toggle`);
    return res.data?.data;
  },

  // 4. Follow-ups
  getFollowUps: async () => {
    const res = await apiClient.get('/worker/follow-ups');
    return res.data?.data;
  },
  createFollowUp: async (followUpData) => {
    const res = await apiClient.post('/worker/follow-ups', followUpData);
    return res.data;
  },
  updateFollowUpStatus: async (id, status = 'Completed') => {
    const res = await apiClient.patch(`/worker/follow-ups/${id}/status`, { status });
    return res.data;
  },

  // 5. Surveys
  getSurveys: async () => {
    const res = await apiClient.get('/worker/surveys');
    return res.data;
  },
  submitSurvey: async (formData) => {
    const res = await apiClient.post('/worker/surveys/submit', formData);
    return res.data;
  },

  // 6. Complaints
  getComplaints: async () => {
    const res = await apiClient.get('/worker/complaints');
    return res.data?.data;
  },
  storeComplaint: async (complaintData) => {
    const res = await apiClient.post('/worker/complaints', complaintData);
    return res.data;
  },
  updateComplaintStatus: async (id, status) => {
    const res = await apiClient.patch(`/worker/complaints/${id}/status`, { status });
    return res.data;
  },

  // Duplicate Detection
  checkDuplicate: async ({ phone, voter_id }) => {
    const res = await apiClient.get('/worker/check-duplicate', { params: { phone, voter_id } });
    return res.data;
  },

  // 6-Level Geographic Hierarchy
  getGeographyHierarchy: async () => {
    const res = await apiClient.get('/worker/geography-hierarchy');
    return res.data?.data;
  },

  // 7. Attendance
  getAttendance: async () => {
    const res = await apiClient.get('/worker/attendance');
    return res.data?.data;
  },
  getAttendancePolicy: async () => {
    const res = await apiClient.get('/worker/attendance-policy');
    return res.data?.data;
  },
  punchIn: async (photo, locationData = {}) => {
    const payload = typeof photo === 'object' && photo !== null && !Array.isArray(photo)
      ? photo
      : { photo, ...(locationData || {}) };
    const res = await apiClient.post('/worker/attendance/punch-in', payload);
    return res.data;
  },
  punchOut: async (photo, locationData = {}) => {
    const payload = typeof photo === 'object' && photo !== null && !Array.isArray(photo)
      ? photo
      : { photo, ...(locationData || {}) };
    const res = await apiClient.post('/worker/attendance/punch-out', payload);
    return res.data;
  },

  // 8. Contacts & Meetings
  getContacts: async () => {
    const res = await apiClient.get('/worker/contacts');
    return res.data?.data;
  },
  recordVisitLog: async (visitData) => {
    const res = await apiClient.post('/worker/contacts/visit-log', visitData);
    return res.data;
  },
  getMeetings: async () => {
    const res = await apiClient.get('/worker/meetings');
    return res.data?.data;
  },
  scheduleMeeting: async (meetingData) => {
    const res = await apiClient.post('/worker/meetings', meetingData);
    return res.data;
  },

  // 9. Candidates & Live Voting
  getCandidates: async () => {
    const res = await apiClient.get('/worker/candidates');
    return res.data?.data;
  },
  castVote: async (candidateId, candidateName) => {
    const res = await apiClient.post('/worker/vote', { candidateId, candidateName });
    return res.data;
  },

  // 10. Karkarta EVM Voting System (karkarta_ tables)
  getEvmElectionData: async () => {
    const res = await apiClient.get('/worker/evm/election');
    return res.data?.data;
  },
  castEvmVote: async (payload) => {
    const res = await apiClient.post('/worker/evm/vote', payload);
    return res.data;
  },
  addEvmCandidate: async (payload) => {
    const res = await apiClient.post('/worker/evm/candidate', payload);
    return res.data;
  },
  updateEvmCandidate: async (id, payload) => {
    const res = await apiClient.put(`/worker/evm/candidate/${id}`, payload);
    return res.data;
  },
  deleteEvmCandidate: async (id) => {
    const res = await apiClient.delete(`/worker/evm/candidate/${id}`);
    return res.data;
  },
  resetEvmVotes: async () => {
    const res = await apiClient.post('/worker/evm/reset-votes');
    return res.data;
  },
  updateEvmSettings: async (payload) => {
    const res = await apiClient.put('/worker/evm/settings', payload);
    return res.data;
  },
  updateEvmElection: async (payload) => {
    const res = await apiClient.put('/worker/evm/election', payload);
    return res.data;
  },
  startEvmElection: async (payload) => {
    const res = await apiClient.post('/worker/evm/start-election', payload);
    return res.data;
  },
  endEvmElection: async () => {
    const res = await apiClient.post('/worker/evm/end-election');
    return res.data;
  },
};
