import React, { useState, useEffect, useRef } from 'react';
import apiClient from '../../api/axios';
import { Send, RefreshCw, X, Bot, Sparkles } from 'lucide-react';

export default function AdminPhoneChatbot({ lang = 'mr', onClose = null }) {
  const isMr = lang === 'mr';

  const [messages, setMessages] = useState([
    {
      id: 'welcome',
      sender: 'bot',
      text: isMr
        ? '👋 नमस्कार अ‍ॅडमिन! मी आपला AI निवडणूक व प्रशासकीय सहाय्यक आहे. मी आपल्याला मतदार, बूथ, गावनिहाय लोकसंख्या, कार्यकर्ते आणि नागरी समस्यांची माहिती देऊ शकतो.'
        : '👋 Hello Admin! I am your AI Campaign & Field Operations Assistant. I can help with voter counts, booths, village demographics, worker attendance, and civic issues.',
      structuredItems: [],
      suggestedActions: [
        { label: isMr ? '🏘️ गावनिहाय लोकसंख्या व मतदार' : '🏘️ Village Population & Voters', query: isMr ? 'गावानुसार एकूण लोकसंख्या आणि मतदार संख्या दाखवा' : 'Show village population and total voters summary' },
        { label: isMr ? '📊 एकूण मतदार सारांश' : '📊 Total Voters Summary', query: isMr ? 'एकूण मतदार संख्या आणि राजकीय कल दाखवा' : 'Show total voters and political sentiment' },
        { label: isMr ? '🗳️ बूथनिहाय प्रगती' : '🗳️ Booth Progress', query: isMr ? 'बूथनिहाय प्रगती आणि आघाडीवरील बूथ दाखवा' : 'Show booth progress' },
        { label: isMr ? '⚠️ प्रलंबित नागरी समस्या' : '⚠️ Pending Issues', query: isMr ? 'सध्याच्या प्रलंबित नागरी समस्या कोणत्या आहेत?' : 'What are pending civic issues?' },
        { label: isMr ? '👥 सक्रिय कार्यकर्ते' : '👥 Active Workers', query: isMr ? 'सक्रिय कार्यकर्ते आणि त्यांची कामगिरी दाखवा' : 'Show active karyakartas' },
      ],
      timestamp: new Date().toLocaleTimeString(isMr ? 'mr-IN' : 'en-IN', { hour: '2-digit', minute: '2-digit' }),
    },
  ]);

  const [input, setInput] = useState('');
  const [loading, setLoading] = useState(false);
  const [suggestions, setSuggestions] = useState([]);
  const chatBottomRef = useRef(null);

  // Load suggestion chips from backend
  useEffect(() => {
    const fetchSuggestions = async () => {
      try {
        const res = await apiClient.get('/admin/chatbot/suggestions', { params: { lang } });
        if (res.data?.success && res.data?.suggestions) {
          setSuggestions(res.data.suggestions);
        }
      } catch (err) {
        console.error('Failed to load chatbot suggestions:', err);
      }
    };
    fetchSuggestions();
  }, [lang]);

  // Auto-scroll to latest message
  useEffect(() => {
    chatBottomRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages, loading]);

  const handleSend = async (queryText = null) => {
    const textToSend = (queryText || input).trim();
    if (!textToSend || loading) return;

    const userMsg = {
      id: `user_${Date.now()}`,
      sender: 'user',
      text: textToSend,
      timestamp: new Date().toLocaleTimeString(isMr ? 'mr-IN' : 'en-IN', { hour: '2-digit', minute: '2-digit' }),
    };

    setMessages((prev) => [...prev, userMsg]);
    setInput('');
    setLoading(true);

    try {
      const res = await apiClient.post('/admin/chatbot/query', {
        message: textToSend,
        lang,
      });

      if (res.data?.success) {
        setMessages((prev) => [
          ...prev,
          {
            id: `bot_${Date.now()}`,
            sender: 'bot',
            text: res.data.reply,
            structuredItems: res.data.related_data || [],
            suggestedActions: res.data.suggested_actions || [],
            timestamp: new Date().toLocaleTimeString(isMr ? 'mr-IN' : 'en-IN', { hour: '2-digit', minute: '2-digit' }),
          },
        ]);
      } else {
        throw new Error(res.data?.message || 'Server returned unsuccessful response');
      }
    } catch (err) {
      console.error('Chatbot error:', err);
      setMessages((prev) => [
        ...prev,
        {
          id: `bot_err_${Date.now()}`,
          sender: 'bot',
          text: isMr
            ? '⚠️ क्षमस्व, विनंतीवर प्रक्रिया करताना त्रुटी आली. कृपया इंटरनेट तपासून पुन्हा प्रयत्न करा.'
            : '⚠️ Sorry, an error occurred while processing your query. Please try again.',
          timestamp: new Date().toLocaleTimeString(isMr ? 'mr-IN' : 'en-IN', { hour: '2-digit', minute: '2-digit' }),
        },
      ]);
    } finally {
      setLoading(false);
    }
  };

  const handleClearChat = () => {
    setMessages([
      {
        id: 'welcome',
        sender: 'bot',
        text: isMr
          ? 'चॅट रीसेट झाले आहे. मी आपल्याला कशी मदत करू?'
          : 'Chat has been reset. How may I assist you?',
        structuredItems: [],
        suggestedActions: [
          { label: isMr ? '🏘️ गाव लोकसंख्या व मतदार' : '🏘️ Village Population', query: isMr ? 'गावानुसार एकूण लोकसंख्या आणि मतदार संख्या दाखवा' : 'Show village population' },
          { label: isMr ? '📊 एकूण मतदार सारांश' : '📊 Total Voters', query: isMr ? 'एकूण मतदार संख्या आणि राजकीय कल दाखवा' : 'Show total voters summary' },
        ],
        timestamp: new Date().toLocaleTimeString(isMr ? 'mr-IN' : 'en-IN', { hour: '2-digit', minute: '2-digit' }),
      },
    ]);
  };

  // Safe and clean Markdown-like parser
  const renderFormattedText = (markdown) => {
    if (!markdown) return null;

    const lines = markdown.split('\n');
    const elements = [];
    let inTable = false;
    let tableRows = [];

    const flushTable = () => {
      if (tableRows.length > 0) {
        const headerRow = tableRows[0];
        const bodyRows = tableRows.slice(1).filter((r) => !r.every((c) => c.match(/^[:\s-]+$/)));

        elements.push(
          <div key={`table_${elements.length}`} style={{ overflowX: 'auto', margin: '8px 0', borderRadius: '8px', border: '1px solid #e2e8f0', WebkitOverflowScrolling: 'touch' }}>
            <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: '11px', textAlign: 'left', background: '#ffffff' }}>
              <thead>
                <tr style={{ background: '#f1f5f9', borderBottom: '2px solid #cbd5e1' }}>
                  {headerRow.map((cell, idx) => (
                    <th key={idx} style={{ padding: '6px 10px', fontWeight: '800', color: '#1e293b', whiteSpace: 'nowrap' }}>
                      {cell.replace(/\*\*/g, '').trim()}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {bodyRows.map((row, rIdx) => (
                  <tr key={rIdx} style={{ borderBottom: '1px solid #f1f5f9', background: rIdx % 2 === 1 ? '#f8fafc' : '#ffffff' }}>
                    {row.map((cell, cIdx) => (
                      <td key={cIdx} style={{ padding: '6px 10px', color: '#334155', whiteSpace: 'nowrap' }}>
                        {formatInline(cell.trim())}
                      </td>
                    ))}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        );
        tableRows = [];
        inTable = false;
      }
    };

    lines.forEach((line, index) => {
      const trimmed = line.trim();

      // Table line
      if (trimmed.startsWith('|') && trimmed.endsWith('|')) {
        inTable = true;
        const cells = trimmed
          .slice(1, -1)
          .split('|')
          .map((c) => c.trim());
        tableRows.push(cells);
        return;
      } else if (inTable) {
        flushTable();
      }

      // Headers
      if (trimmed.startsWith('### ')) {
        elements.push(
          <h4 key={index} style={{ margin: '8px 0 4px 0', fontSize: '13px', fontWeight: '800', color: '#0f172a' }}>
            {formatInline(trimmed.substring(4))}
          </h4>
        );
      } else if (trimmed.startsWith('* ') || trimmed.startsWith('• ') || trimmed.startsWith('- ')) {
        // Bullet
        elements.push(
          <div key={index} style={{ display: 'flex', gap: '6px', margin: '3px 0', fontSize: '12.5px', color: '#334155', lineHeight: 1.4 }}>
            <span style={{ color: '#ea580c', fontWeight: 'bold' }}>•</span>
            <div>{formatInline(trimmed.substring(2))}</div>
          </div>
        );
      } else if (trimmed) {
        // Normal paragraph
        elements.push(
          <p key={index} style={{ margin: '4px 0', fontSize: '12.5px', lineHeight: 1.45, color: '#334155' }}>
            {formatInline(trimmed)}
          </p>
        );
      }
    });

    if (inTable) {
      flushTable();
    }

    return elements;
  };

  const formatInline = (str) => {
    if (!str) return '';
    const parts = [];
    const regex = /(\*\*.*?\*\*|`.*?`)/g;
    let match;
    let lastIndex = 0;
    let keyIdx = 0;

    while ((match = regex.exec(str)) !== null) {
      if (match.index > lastIndex) {
        parts.push(str.substring(lastIndex, match.index));
      }
      const token = match[0];
      if (token.startsWith('**') && token.endsWith('**')) {
        parts.push(<strong key={keyIdx++} style={{ color: '#0f172a' }}>{token.slice(2, -2)}</strong>);
      } else if (token.startsWith('`') && token.endsWith('`')) {
        parts.push(
          <code key={keyIdx++} style={{ background: '#f1f5f9', padding: '1px 4px', borderRadius: '4px', fontSize: '11px', color: '#ea580c' }}>
            {token.slice(1, -1)}
          </code>
        );
      }
      lastIndex = regex.lastIndex;
    }

    if (lastIndex < str.length) {
      parts.push(str.substring(lastIndex));
    }

    return parts.length > 0 ? parts : str;
  };

  return (
    <div
      style={{
        display: 'flex',
        flexDirection: 'column',
        height: '100%',
        minHeight: 0,
        maxHeight: '100%',
        background: '#ffffff',
        fontFamily: isMr ? "'Noto Sans Devanagari', 'Mukta', 'Inter', sans-serif" : "'Inter', sans-serif",
        overflow: 'hidden',
      }}
    >
      {/* Top Header Bar */}
      <div
        style={{
          background: 'linear-gradient(180deg, #071e3b 0%, #0c2b54 100%)',
          color: '#ffffff',
          padding: '10px 14px 12px 14px',
          display: 'flex',
          flexDirection: 'column',
          flexShrink: 0,
          borderBottom: '1px solid rgba(255, 255, 255, 0.1)',
        }}
      >
        {/* Mobile Pull Handle Indicator */}
        <div style={{ display: 'flex', justifyContent: 'center', width: '100%', marginBottom: '6px' }}>
          <div style={{ width: '38px', height: '4px', background: 'rgba(255, 255, 255, 0.35)', borderRadius: '999px' }} />
        </div>

        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: '8px' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '8px', minWidth: 0, flex: 1 }}>
            <div
              style={{
                width: '34px',
                height: '34px',
                borderRadius: '10px',
                background: 'linear-gradient(135deg, #ff7e2a 0%, #ff5200 100%)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                fontSize: '18px',
                flexShrink: 0,
                boxShadow: '0 4px 12px rgba(255, 82, 0, 0.35)',
              }}
            >
              🤖
            </div>
            <div style={{ minWidth: 0, flex: 1 }}>
              <div style={{ display: 'flex', alignItems: 'center', gap: '6px', minWidth: 0 }}>
                <h3
                  style={{
                    margin: 0,
                    fontSize: '14px',
                    fontWeight: '800',
                    whiteSpace: 'nowrap',
                    overflow: 'hidden',
                    textOverflow: 'ellipsis',
                    color: '#ffffff',
                  }}
                >
                  {isMr ? 'AI प्रशासकीय सहाय्यक' : 'AI Admin Assistant'}
                </h3>
                <span
                  style={{
                    background: '#10b981',
                    color: '#ffffff',
                    fontSize: '9.5px',
                    fontWeight: '800',
                    padding: '2px 6px',
                    borderRadius: '10px',
                    display: 'inline-flex',
                    alignItems: 'center',
                    gap: '3px',
                    flexShrink: 0,
                  }}
                >
                  <span style={{ width: '4px', height: '4px', borderRadius: '50%', background: '#ffffff' }} />
                  LIVE
                </span>
              </div>
              <div
                style={{
                  fontSize: '10.5px',
                  color: '#94a3b8',
                  marginTop: '1px',
                  whiteSpace: 'nowrap',
                  overflow: 'hidden',
                  textOverflow: 'ellipsis',
                }}
              >
                {isMr ? 'मतदार, लोकसंख्या व बूथ माहिती' : 'Voters, Demographics & Booth Intelligence'}
              </div>
            </div>
          </div>

          <div style={{ display: 'flex', alignItems: 'center', gap: '6px', flexShrink: 0 }}>
            <button
              type="button"
              onClick={handleClearChat}
              title={isMr ? 'चॅट रीसेट करा' : 'Clear chat'}
              style={{
                background: 'rgba(255, 255, 255, 0.12)',
                border: '1px solid rgba(255, 255, 255, 0.2)',
                color: '#ffffff',
                padding: '5px 8px',
                borderRadius: '6px',
                fontSize: '11px',
                fontWeight: '600',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                gap: '4px',
                whiteSpace: 'nowrap',
              }}
            >
              <RefreshCw size={11} /> <span>{isMr ? 'रीसेट' : 'Reset'}</span>
            </button>

            {onClose && (
              <button
                type="button"
                onClick={onClose}
                title={isMr ? 'बंद करा' : 'Close'}
                aria-label={isMr ? 'बंद करा' : 'Close'}
                style={{
                  background: 'rgba(255, 255, 255, 0.15)',
                  border: '1px solid rgba(255, 255, 255, 0.25)',
                  color: '#ffffff',
                  width: '32px',
                  height: '32px',
                  borderRadius: '8px',
                  fontSize: '14px',
                  cursor: 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  padding: 0,
                  flexShrink: 0,
                  transition: 'background 0.15s ease',
                }}
                onMouseEnter={(e) => (e.currentTarget.style.background = '#ef4444')}
                onMouseLeave={(e) => (e.currentTarget.style.background = 'rgba(255, 255, 255, 0.15)')}
              >
                ✕
              </button>
            )}
          </div>
        </div>
      </div>

      {/* Quick Suggestion Chips Bar */}
      <div
        style={{
          background: '#f8fafc',
          borderBottom: '1px solid #e2e8f0',
          padding: '8px 12px',
          display: 'flex',
          gap: '6px',
          overflowX: 'auto',
          whiteSpace: 'nowrap',
          scrollbarWidth: 'none',
          WebkitOverflowScrolling: 'touch',
          flexShrink: 0,
        }}
      >
        <span style={{ fontSize: '11px', fontWeight: '750', color: '#64748b', alignSelf: 'center', marginRight: '4px', flexShrink: 0 }}>
          {isMr ? 'त्वरित प्रश्न:' : 'Quick Queries:'}
        </span>
        {suggestions.map((sug) => (
          <button
            key={sug.id}
            type="button"
            onClick={() => handleSend(sug.query)}
            disabled={loading}
            style={{
              background: '#ffffff',
              border: '1px solid #cbd5e1',
              borderRadius: '20px',
              padding: '4px 10px',
              fontSize: '11px',
              fontWeight: '650',
              color: '#c2410c',
              cursor: 'pointer',
              display: 'inline-flex',
              alignItems: 'center',
              gap: '4px',
              flexShrink: 0,
              boxShadow: '0 1px 2px rgba(0,0,0,0.03)',
            }}
          >
            {sug.label}
          </button>
        ))}
      </div>

      {/* Chat Messages Stream */}
      <div
        style={{
          flex: 1,
          minHeight: 0,
          overflowY: 'auto',
          padding: '12px 10px',
          display: 'flex',
          flexDirection: 'column',
          gap: '12px',
          background: '#f8fafc',
          WebkitOverflowScrolling: 'touch',
        }}
      >
        {messages.map((msg) => {
          const isUser = msg.sender === 'user';
          return (
            <div
              key={msg.id}
              style={{
                display: 'flex',
                flexDirection: 'column',
                alignItems: isUser ? 'flex-end' : 'flex-start',
                width: '100%',
              }}
            >
              <div
                style={{
                  maxWidth: '94%',
                  background: isUser ? 'linear-gradient(135deg, #c2410c 0%, #ea580c 100%)' : '#ffffff',
                  color: isUser ? '#ffffff' : '#0f172a',
                  padding: '10px 14px',
                  borderRadius: isUser ? '16px 16px 4px 16px' : '16px 16px 16px 4px',
                  boxShadow: '0 2px 6px rgba(0,0,0,0.05)',
                  border: isUser ? 'none' : '1px solid #e2e8f0',
                  boxSizing: 'border-box',
                  wordBreak: 'break-word',
                  overflowWrap: 'anywhere',
                }}
              >
                {/* Message Header */}
                <div
                  style={{
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    marginBottom: '4px',
                    fontSize: '10.5px',
                    color: isUser ? '#fed7aa' : '#94a3b8',
                  }}
                >
                  <span style={{ fontWeight: '750' }}>{isUser ? (isMr ? 'तुम्ही (Admin)' : 'You (Admin)') : (isMr ? '🤖 AI सहाय्यक' : '🤖 AI Bot')}</span>
                  <span>{msg.timestamp}</span>
                </div>

                {/* Message Content */}
                {isUser ? (
                  <div style={{ fontSize: '13px', lineHeight: 1.45, whiteSpace: 'pre-wrap' }}>{msg.text}</div>
                ) : (
                  <div>{renderFormattedText(msg.text)}</div>
                )}

                {/* Structured Interactive Clickable Items (e.g. Villages, Booths, Issues) */}
                {msg.structuredItems && msg.structuredItems.length > 0 && (
                  <div style={{ marginTop: '10px', display: 'flex', flexDirection: 'column', gap: '6px' }}>
                    <div style={{ fontSize: '10.5px', fontWeight: '800', color: '#64748b', textTransform: 'uppercase' }}>
                      {isMr ? '📌 संबंधित तपशील (क्लिक करा):' : '📌 Related Records:'}
                    </div>
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr', gap: '6px' }}>
                      {msg.structuredItems.map((item, idx) => (
                        <div
                          key={idx}
                          onClick={() => {
                            if (item.type === 'booth') {
                              handleSend(`${item.title} ची लोकसंख्या आणि मतदार माहिती`);
                            } else if (item.type === 'issue') {
                              handleSend(`तक्रार ${item.title} अपडेट`);
                            } else if (item.type === 'citizen') {
                              handleSend(`${item.title} यांची मतदार माहिती`);
                            }
                          }}
                          style={{
                            background: '#f8fafc',
                            border: '1px solid #cbd5e1',
                            borderRadius: '8px',
                            padding: '8px 10px',
                            cursor: 'pointer',
                          }}
                        >
                          <div style={{ fontSize: '12px', fontWeight: '750', color: '#ea580c' }}>
                            {item.title}
                          </div>
                          <div style={{ fontSize: '11px', color: '#64748b', marginTop: '2px' }}>
                            {item.subtitle}
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                {/* Suggested Follow-up Actions */}
                {msg.suggestedActions && msg.suggestedActions.length > 0 && (
                  <div style={{ marginTop: '10px', paddingTop: '8px', borderTop: '1px solid #e2e8f0', display: 'flex', gap: '6px', flexWrap: 'wrap' }}>
                    {msg.suggestedActions.map((act, aIdx) => (
                      <button
                        key={aIdx}
                        type="button"
                        onClick={() => handleSend(act.query)}
                        style={{
                          background: '#fff7ed',
                          border: '1px solid #ffedd5',
                          borderRadius: '6px',
                          padding: '3px 8px',
                          fontSize: '10.5px',
                          fontWeight: '700',
                          color: '#c2410c',
                          cursor: 'pointer',
                        }}
                      >
                        ↳ {act.label}
                      </button>
                    ))}
                  </div>
                )}
              </div>
            </div>
          );
        })}

        {/* Loading Indicator */}
        {loading && (
          <div style={{ display: 'flex', alignItems: 'center', gap: '8px', background: '#ffffff', padding: '10px 14px', borderRadius: '12px', border: '1px solid #e2e8f0', width: 'fit-content' }}>
            <span style={{ fontSize: '16px' }}>🤖</span>
            <span style={{ fontSize: '12px', fontWeight: '700', color: '#1e293b' }}>
              {isMr ? 'डेटाबेसमधून माहिती शोधत आहे...' : 'Analyzing database records...'}
            </span>
          </div>
        )}

        <div ref={chatBottomRef} />
      </div>

      {/* Input Form Bar */}
      <div
        style={{
          background: '#ffffff',
          borderTop: '1px solid #e2e8f0',
          padding: '8px 10px',
          paddingBottom: 'max(10px, env(safe-area-inset-bottom, 10px))',
          display: 'flex',
          gap: '6px',
          alignItems: 'center',
          flexShrink: 0,
        }}
      >
        <input
          type="text"
          value={input}
          onChange={(e) => setInput(e.target.value)}
          onKeyDown={(e) => {
            if (e.key === 'Enter') {
              e.preventDefault();
              handleSend();
            }
          }}
          placeholder={isMr ? 'उदा. "माधवनगर लोकसंख्या", "बूथ १"...' : 'E.g. "Madhavnagar population"...'}
          disabled={loading}
          style={{
            flex: 1,
            minWidth: 0,
            padding: '9px 12px',
            borderRadius: '10px',
            border: '1px solid #cbd5e1',
            fontSize: '13px',
            outline: 'none',
            background: loading ? '#f8fafc' : '#ffffff',
          }}
        />

        <button
          type="button"
          onClick={() => handleSend()}
          disabled={loading || !input.trim()}
          style={{
            background: loading || !input.trim() ? '#cbd5e1' : 'linear-gradient(90deg, #ff6b18 0%, #ff5200 100%)',
            color: '#ffffff',
            border: 'none',
            padding: '9px 14px',
            borderRadius: '10px',
            fontSize: '12px',
            fontWeight: '750',
            cursor: loading || !input.trim() ? 'not-allowed' : 'pointer',
            display: 'flex',
            alignItems: 'center',
            gap: '4px',
            whiteSpace: 'nowrap',
            flexShrink: 0,
            boxShadow: loading || !input.trim() ? 'none' : '0 3px 10px rgba(255, 82, 0, 0.35)',
            transition: 'all 0.15s ease',
          }}
        >
          {loading ? (isMr ? 'शोधत...' : 'Searching...') : (isMr ? 'विचारा ➔' : 'Ask ➔')}
        </button>
      </div>
    </div>
  );
}
