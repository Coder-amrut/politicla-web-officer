import React from 'react';
import {
  CheckSquare,
  Plus,
  PhoneCall
} from 'lucide-react';

export default function TasksTab({
  isMr,
  t,
  tasksList = [],
  followUpsList = [],
  taskActiveTab = 'tasks',
  setTaskActiveTab,
  isAddingTask,
  setIsAddingTask,
  newTaskData,
  setNewTaskData,
  handleCreateTask,
  handleToggleTask,
  isAddingFollowUp,
  setIsAddingFollowUp,
  newFollowUpData,
  setNewFollowUpData,
  handleCreateFollowUp,
  handleUpdateFollowUpStatus,
}) {
  const completedTasksCount = tasksList.filter((t) => t.completed).length;
  const pendingTasksCount = tasksList.length - completedTasksCount;

  return (
    <div className="module-container" style={{ display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>
      {/* Header */}
      <div className="page-header" style={{ marginBottom: 0, display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: '12px' }}>
        <div>
          <h1 className="page-title" style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
            <CheckSquare size={28} color="#2563eb" />
            <span>{isMr ? 'कार्ये व मतदार पाठपुरावा' : 'Tasks & Citizen Follow-ups'}</span>
          </h1>
          <div className="page-subtitle">
            {isMr ? 'दैनंदिन कामांचे नियोजन करा, पूर्ण झालेली कामे चिन्हांकित करा आणि नागरिकांशी नियमित संपर्क ठेवा.' : 'Manage daily tasks, mark completions, and follow up with voters.'}
          </div>
        </div>

        {/* View Switcher */}
        <div style={{ display: 'flex', gap: '6px', background: '#ffffff', padding: '4px', borderRadius: '10px', border: '1px solid #e2e8f0' }}>
          <button
            type="button"
            onClick={() => setTaskActiveTab('tasks')}
            style={{
              padding: '7px 16px',
              borderRadius: '7px',
              border: 'none',
              fontWeight: 700,
              fontSize: '12.5px',
              cursor: 'pointer',
              background: taskActiveTab === 'tasks' ? '#2563eb' : 'transparent',
              color: taskActiveTab === 'tasks' ? '#ffffff' : '#64748b',
              transition: 'all 0.15s ease'
            }}
          >
            📋 {isMr ? `दैनंदिन कामे (${tasksList.length})` : `Tasks (${tasksList.length})`}
          </button>

          <button
            type="button"
            onClick={() => setTaskActiveTab('followups')}
            style={{
              padding: '7px 16px',
              borderRadius: '7px',
              border: 'none',
              fontWeight: 700,
              fontSize: '12.5px',
              cursor: 'pointer',
              background: taskActiveTab === 'followups' ? '#ea580c' : 'transparent',
              color: taskActiveTab === 'followups' ? '#ffffff' : '#64748b',
              transition: 'all 0.15s ease'
            }}
          >
            🤝 {isMr ? `मतदार पाठपुरावा (${followUpsList.length})` : `Follow-ups (${followUpsList.length})`}
          </button>
        </div>
      </div>

      {/* Section 1: My Daily Tasks */}
      {taskActiveTab === 'tasks' && (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <div style={{ fontSize: '13px', color: '#64748b', fontWeight: '700' }}>
              {isMr ? `एकूण कामे: ${tasksList.length} | पूर्ण: ${completedTasksCount} | शिल्लक: ${pendingTasksCount}` : `Total: ${tasksList.length} | Done: ${completedTasksCount} | Pending: ${pendingTasksCount}`}
            </div>
            <button
              type="button"
              className="btn-primary"
              onClick={() => setIsAddingTask(true)}
              style={{ display: 'flex', alignItems: 'center', gap: '6px', padding: '8px 16px', fontSize: '13px', fontWeight: 700 }}
            >
              <Plus size={16} />
              <span>{isMr ? '+ नवीन काम जोडा' : '+ Add Task'}</span>
            </button>
          </div>

          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(320px, 1fr))', gap: '14px' }}>
            {tasksList.map((task) => (
              <div
                key={task.id}
                className="card"
                style={{
                  padding: '16px',
                  borderRadius: '12px',
                  border: task.completed ? '1px solid #bbf7d0' : '1px solid #e2e8f0',
                  background: task.completed ? '#f0fdf4' : '#ffffff',
                  display: 'flex',
                  flexDirection: 'column',
                  justifyContent: 'space-between',
                  gap: '12px',
                  transition: 'all 0.15s ease'
                }}
              >
                <div style={{ display: 'flex', alignItems: 'flex-start', gap: '12px' }}>
                  <input
                    type="checkbox"
                    checked={Boolean(task.completed)}
                    onChange={() => handleToggleTask(task.id)}
                    style={{ width: '18px', height: '18px', accentColor: '#16a34a', marginTop: '2px', cursor: 'pointer' }}
                  />
                  <div style={{ flex: 1 }}>
                    <div style={{
                      fontSize: '14.5px',
                      fontWeight: 750,
                      color: task.completed ? '#15803d' : '#0f172a',
                      textDecoration: task.completed ? 'line-through' : 'none'
                    }}>
                      {task.title}
                    </div>
                    <div style={{ fontSize: '11.5px', color: '#64748b', marginTop: '4px' }}>
                      🏷️ {task.category || 'बूथ कार्य'}
                    </div>
                  </div>
                </div>

                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', paddingTop: '8px', borderTop: '1px solid rgba(0,0,0,0.06)', fontSize: '11.5px' }}>
                  <span style={{ color: '#64748b' }}>
                    📅 {isMr ? 'मुदत:' : 'Due:'} {task.deadline || 'आज'}
                  </span>
                  <span style={{
                    padding: '2px 8px',
                    borderRadius: '10px',
                    fontWeight: 700,
                    fontSize: '10.5px',
                    background: task.priority === 'High' ? '#fef2f2' : task.priority === 'Urgent' ? '#fff1f2' : '#eff6ff',
                    color: task.priority === 'High' ? '#b91c1c' : task.priority === 'Urgent' ? '#be123c' : '#1d4ed8',
                    border: '1px solid currentColor'
                  }}>
                    {task.priority || 'Medium'}
                  </span>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Section 2: Citizen Follow-ups */}
      {taskActiveTab === 'followups' && (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <div style={{ fontSize: '13px', color: '#64748b', fontWeight: '700' }}>
              {isMr ? 'प्रलंबित मतदार भेटी व संवादाचा पाठपुरावा' : 'Pending voter visits and communication follow-ups'}
            </div>
            <button
              type="button"
              className="btn-primary"
              onClick={() => setIsAddingFollowUp(true)}
              style={{ display: 'flex', alignItems: 'center', gap: '6px', padding: '8px 16px', fontSize: '13px', fontWeight: 700 }}
            >
              <Plus size={16} />
              <span>{isMr ? '+ नवीन पाठपुरावा नोंदवा' : '+ Add Follow-up'}</span>
            </button>
          </div>

          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(320px, 1fr))', gap: '14px' }}>
            {followUpsList.map((item) => (
              <div
                key={item.id}
                className="card"
                style={{
                  padding: '16px',
                  borderRadius: '12px',
                  border: item.status === 'Completed' ? '1px solid #bbf7d0' : '1px solid #e2e8f0',
                  background: item.status === 'Completed' ? '#f0fdf4' : '#ffffff',
                  display: 'flex',
                  flexDirection: 'column',
                  justifyContent: 'space-between',
                  gap: '12px'
                }}
              >
                <div>
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '6px' }}>
                    <div>
                      <div style={{ fontSize: '15px', fontWeight: 800, color: '#0f172a' }}>{item.citizen_name}</div>
                      <div style={{ fontSize: '12px', color: '#64748b' }}>📞 {item.phone || '-'}</div>
                    </div>
                    <span style={{
                      padding: '3px 8px',
                      borderRadius: '8px',
                      fontSize: '11px',
                      fontWeight: 700,
                      background: item.status === 'Completed' ? '#dcfce7' : '#fff7ed',
                      color: item.status === 'Completed' ? '#15803d' : '#c2410c',
                      border: item.status === 'Completed' ? '1px solid #86efac' : '1px solid #fed7aa'
                    }}>
                      {item.status === 'Completed' ? (isMr ? '✓ पूर्ण' : 'Done') : (isMr ? '⏳ प्रलंबित' : 'Pending')}
                    </span>
                  </div>

                  <div style={{ fontSize: '12.5px', color: '#334155', background: '#f8fafc', padding: '8px 10px', borderRadius: '6px', border: '1px solid #e2e8f0', marginTop: '6px' }}>
                    💬 {item.topic}
                  </div>
                </div>

                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', paddingTop: '8px', borderTop: '1px solid rgba(0,0,0,0.06)' }}>
                  <span style={{ fontSize: '11.5px', color: '#64748b' }}>
                    📅 {item.next_date || 'लवकरच'}
                  </span>

                  <div style={{ display: 'flex', gap: '6px' }}>
                    {item.phone && (
                      <button
                        type="button"
                        className="icon-btn icon-btn-primary"
                        title="Call"
                        onClick={() => alert(`Calling ${item.citizen_name} (${item.phone})...`)}
                      >
                        <PhoneCall size={14} color="#2563eb" />
                      </button>
                    )}
                    {item.status !== 'Completed' && (
                      <button
                        type="button"
                        onClick={() => handleUpdateFollowUpStatus(item.id, 'Completed')}
                        style={{
                          border: 'none',
                          background: '#16a34a',
                          color: '#ffffff',
                          padding: '4px 10px',
                          borderRadius: '6px',
                          fontSize: '11.5px',
                          fontWeight: 700,
                          cursor: 'pointer'
                        }}
                      >
                        ✓ {isMr ? 'पूर्ण झाले' : 'Mark Done'}
                      </button>
                    )}
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Add Task Modal */}
      {isAddingTask && (
        <div className="responsive-modal-overlay">
          <div className="responsive-modal-content" style={{ maxWidth: '440px' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '14px' }}>
              <h3 style={{ margin: 0, fontSize: '16px', fontWeight: 800 }}>{isMr ? 'नवीन काम जोडा' : 'Add New Task'}</h3>
              <button type="button" onClick={() => setIsAddingTask(false)} style={{ border: 'none', background: 'none', cursor: 'pointer', fontSize: '18px', color: '#94a3b8' }}>✕</button>
            </div>
            <form onSubmit={handleCreateTask} style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
              <div>
                <label style={{ display: 'block', fontSize: '12px', fontWeight: 700, marginBottom: '4px' }}>{isMr ? 'कामाचे नाव / शीर्षक *' : 'Task Title *'}</label>
                <input type="text" required className="form-input" placeholder={isMr ? "उदा. बूथ क्र. ४ मतदार यादी पडताळणी" : "Enter title"} value={newTaskData.title} onChange={(e) => setNewTaskData({ ...newTaskData, title: e.target.value })} />
              </div>
              <div>
                <label style={{ display: 'block', fontSize: '12px', fontWeight: 700, marginBottom: '4px' }}>{isMr ? 'कामाचा वर्ग' : 'Category'}</label>
                <select className="form-input" value={newTaskData.category} onChange={(e) => setNewTaskData({ ...newTaskData, category: e.target.value })}>
                  <option value="बूथ प्रचार (Booth Outreach)">बूथ प्रचार (Booth Outreach)</option>
                  <option value="मतदार संपर्क (Voter Contact)">मतदार संपर्क (Voter Contact)</option>
                  <option value="पत्रक वाटप (Pamphlet Distribution)">पत्रक वाटप (Pamphlet Distribution)</option>
                  <option value="अहवाल सादर करणे (Reporting)">अहवाल सादर करणे (Reporting)</option>
                </select>
              </div>
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '10px' }}>
                <div>
                  <label style={{ display: 'block', fontSize: '12px', fontWeight: 700, marginBottom: '4px' }}>{isMr ? 'मुदत (Deadline)' : 'Deadline'}</label>
                  <input type="text" className="form-input" placeholder="उदा. उद्या सायं" value={newTaskData.deadline} onChange={(e) => setNewTaskData({ ...newTaskData, deadline: e.target.value })} />
                </div>
                <div>
                  <label style={{ display: 'block', fontSize: '12px', fontWeight: 700, marginBottom: '4px' }}>{isMr ? 'प्राधान्य' : 'Priority'}</label>
                  <select className="form-input" value={newTaskData.priority} onChange={(e) => setNewTaskData({ ...newTaskData, priority: e.target.value })}>
                    <option value="High">🔴 High</option>
                    <option value="Medium">🟡 Medium</option>
                    <option value="Normal">🟢 Normal</option>
                  </select>
                </div>
              </div>
              <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '8px', marginTop: '6px' }}>
                <button type="button" className="btn-outline" onClick={() => setIsAddingTask(false)}>{t('cancel')}</button>
                <button type="submit" className="btn-primary" style={{ fontWeight: 750 }}>✓ {isMr ? 'काम जोडा' : 'Add Task'}</button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Add Follow-up Modal */}
      {isAddingFollowUp && (
        <div className="responsive-modal-overlay">
          <div className="responsive-modal-content" style={{ maxWidth: '440px' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '14px' }}>
              <h3 style={{ margin: 0, fontSize: '16px', fontWeight: 800 }}>{isMr ? 'नवीन पाठपुरावा नोंदवा' : 'Add Follow-up'}</h3>
              <button type="button" onClick={() => setIsAddingFollowUp(false)} style={{ border: 'none', background: 'none', cursor: 'pointer', fontSize: '18px', color: '#94a3b8' }}>✕</button>
            </div>
            <form onSubmit={handleCreateFollowUp} style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
              <div>
                <label style={{ display: 'block', fontSize: '12px', fontWeight: 700, marginBottom: '4px' }}>{isMr ? 'नागरिकाचे नाव *' : 'Citizen Name *'}</label>
                <input type="text" required className="form-input" placeholder="उदा. अशोक कदम" value={newFollowUpData.citizen_name} onChange={(e) => setNewFollowUpData({ ...newFollowUpData, citizen_name: e.target.value })} />
              </div>
              <div>
                <label style={{ display: 'block', fontSize: '12px', fontWeight: 700, marginBottom: '4px' }}>{isMr ? 'फोन नंबर' : 'Phone'}</label>
                <input type="tel" maxLength="10" className="form-input" placeholder="९८XXXXXXXX" value={newFollowUpData.phone} onChange={(e) => setNewFollowUpData({ ...newFollowUpData, phone: e.target.value })} />
              </div>
              <div>
                <label style={{ display: 'block', fontSize: '12px', fontWeight: 700, marginBottom: '4px' }}>{isMr ? 'पाठपुराव्याचा विषय *' : 'Topic *'}</label>
                <input type="text" required className="form-input" placeholder="उदा. रस्ता कामाची पाहणी, मतदार यादी नाव नोंदणी" value={newFollowUpData.topic} onChange={(e) => setNewFollowUpData({ ...newFollowUpData, topic: e.target.value })} />
              </div>
              <div>
                <label style={{ display: 'block', fontSize: '12px', fontWeight: 700, marginBottom: '4px' }}>{isMr ? 'पुढील संपर्काची तारीख' : 'Next Follow-up Date'}</label>
                <input type="text" className="form-input" placeholder="उदा. २० ऑक्टोबर सायं ५ वाजता" value={newFollowUpData.next_date} onChange={(e) => setNewFollowUpData({ ...newFollowUpData, next_date: e.target.value })} />
              </div>
              <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '8px', marginTop: '6px' }}>
                <button type="button" className="btn-outline" onClick={() => setIsAddingFollowUp(false)}>{t('cancel')}</button>
                <button type="submit" className="btn-success" style={{ fontWeight: 750 }}>✓ {isMr ? 'नोंदवा' : 'Save'}</button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
