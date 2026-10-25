import React from 'react';
import { useNavigate } from 'react-router-dom';

/**
 * Modern, reusable BackButton for all pages.
 * Supports smart history back, custom onBack handler, fallback route, Marathi/English, and stylish themes.
 */
export default function BackButton({
  fallbackPath = '/',
  label,
  lang = 'en',
  style = {},
  variant = 'default', // 'default' | 'dark' | 'light' | 'ghost' | 'blue'
  onBack,
}) {
  const navigate = useNavigate();

  const handleBack = () => {
    if (onBack) {
      onBack();
      return;
    }
    if (window.history.length > 1) {
      navigate(-1);
    } else {
      navigate(fallbackPath);
    }
  };

  const defaultLabel = lang === 'mr' ? 'मागे जा' : 'Back';
  const displayLabel = label !== undefined ? label : defaultLabel;

  const variantStyles = {
    default: {
      background: '#ffffff',
      border: '1px solid #cbd5e1',
      color: '#334155',
      boxShadow: '0 1px 2px rgba(0, 0, 0, 0.05)',
      hoverBg: '#f8fafc',
      hoverBorder: '#94a3b8',
    },
    dark: {
      background: '#1e293b',
      border: '1px solid #334155',
      color: '#e2e8f0',
      boxShadow: '0 2px 4px rgba(0, 0, 0, 0.2)',
      hoverBg: '#334155',
      hoverBorder: '#60a5fa',
    },
    light: {
      background: '#f1f5f9',
      border: '1px solid #e2e8f0',
      color: '#475569',
      boxShadow: 'none',
      hoverBg: '#e2e8f0',
      hoverBorder: '#cbd5e1',
    },
    blue: {
      background: 'rgba(37, 99, 235, 0.1)',
      border: '1px solid rgba(37, 99, 235, 0.3)',
      color: '#2563eb',
      boxShadow: 'none',
      hoverBg: 'rgba(37, 99, 235, 0.18)',
      hoverBorder: '#2563eb',
    },
    ghost: {
      background: 'transparent',
      border: '1px solid transparent',
      color: '#64748b',
      boxShadow: 'none',
      hoverBg: 'rgba(0, 0, 0, 0.05)',
      hoverBorder: 'rgba(0, 0, 0, 0.1)',
    },
  };

  const currentTheme = variantStyles[variant] || variantStyles.default;

  return (
    <button
      type="button"
      onClick={handleBack}
      style={{
        display: 'inline-flex',
        alignItems: 'center',
        gap: '6px',
        padding: '6.5px 12px',
        borderRadius: '8px',
        fontSize: '13px',
        fontWeight: '650',
        cursor: 'pointer',
        transition: 'all 0.16s ease',
        background: currentTheme.background,
        border: currentTheme.border,
        color: currentTheme.color,
        boxShadow: currentTheme.boxShadow,
        outline: 'none',
        userSelect: 'none',
        ...style,
      }}
      onMouseOver={(e) => {
        if (currentTheme.hoverBg) e.currentTarget.style.background = currentTheme.hoverBg;
        if (currentTheme.hoverBorder) e.currentTarget.style.borderColor = currentTheme.hoverBorder;
      }}
      onMouseOut={(e) => {
        e.currentTarget.style.background = currentTheme.background;
        const origBorder = currentTheme.border.split(' ');
        e.currentTarget.style.borderColor = origBorder[2] || '#cbd5e1';
      }}
      title={displayLabel || 'Go back'}
    >
      <svg
        width="15"
        height="15"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="2.2"
        strokeLinecap="round"
        strokeLinejoin="round"
      >
        <line x1="19" y1="12" x2="5" y2="12" />
        <polyline points="12 19 5 12 12 5" />
      </svg>
      {displayLabel && <span>{displayLabel}</span>}
    </button>
  );
}
