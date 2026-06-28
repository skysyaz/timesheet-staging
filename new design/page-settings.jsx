import { useState } from "react";

const T = {
  primary: "#1B3860", primaryDk: "#102648", primarySoft: "#EAF0F8", accent: "#8B1520",
  ink: "#0F172A", body: "#475569", muted: "#94A3B8",
  line: "#E2E8F0", bg: "#F8FAFC", card: "#FFFFFF",
  success: "#047857", successSoft: "#ECFDF5", successRing: "#A7F3D0",
  warning: "#B45309", warningSoft: "#FFFBEB", warningRing: "#FDE68A",
  danger: "#BE123C", dangerSoft: "#FFF1F2", dangerRing: "#FECDD3",
  info: "#1D4ED8", infoSoft: "#EFF6FF", infoRing: "#BFDBFE",
};

const ICONS = {
  menu: <><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></>,
  clock: <><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3.2 2"/></>,
  "check-circle": <><circle cx="12" cy="12" r="8.5"/><path d="M8.3 12.3l2.4 2.4 4.6-5.4"/></>,
  bell: <><path d="M7 17.5h10"/><path d="M6.2 17.5a2 4 0 002-4.2V10.5a3.8 3.8 0 117.6 0v2.8a2 4 0 002 4.2"/><path d="M10.4 20a1.7 1.7 0 003.2 0"/></>,
  "information-circle": <><circle cx="12" cy="12" r="8.5"/><line x1="12" y1="11" x2="12" y2="16"/><circle cx="12" cy="8" r="0.6" fill="currentColor" stroke="none"/></>,
  "exclamation-triangle": <><path d="M12 4.5L21 19H3L12 4.5z"/><line x1="12" y1="10" x2="12" y2="14.3"/><circle cx="12" cy="17" r="0.6" fill="currentColor" stroke="none"/></>,
  check: <polyline points="5 12.5 9.5 17 19 6" />,
  "x-mark": <><line x1="6" y1="6" x2="18" y2="18"/><line x1="18" y1="6" x2="6" y2="18"/></>,
};
function Icon({ name, size = 16, color = "currentColor" }) {
  return (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke={color}
      strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round" style={{ display: "block", flexShrink: 0 }}>
      {ICONS[name]}
    </svg>
  );
}

const QUATRIZ_LOGO = "data:image/webp;base64,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";

function QuatrizLogo({ height = 18, light = false }) {
  return (
    <img
      src={QUATRIZ_LOGO}
      alt="Quatriz"
      draggable={false}
      style={{
        height,
        width: "auto",
        display: "block",
        userSelect: "none",
        filter: light ? "brightness(0) invert(1)" : "none",
      }}
    />
  );
}

function TopBar() {
  return (
    <div style={{
      background: T.card, borderBottom: `1px solid ${T.line}`, padding: "11px 16px",
      display: "flex", alignItems: "center", justifyContent: "space-between",
      position: "sticky", top: 0, zIndex: 100,
    }}>
      <div style={{ display: "flex", alignItems: "center", gap: 12 }}>
        <span style={{ color: T.body, display: "flex" }}><Icon name="menu" size={19} /></span>
        <div style={{ width: 1, height: 18, background: T.line }} />
        <QuatrizLogo height={22} />
      </div>
      <div style={{
        width: 30, height: 30, borderRadius: "50%", background: T.primarySoft, color: T.primary,
        display: "flex", alignItems: "center", justifyContent: "center",
        fontSize: 12, fontWeight: 700, border: `1px solid ${T.line}`,
      }}>A</div>
    </div>
  );
}

/* Toggle switch */
function Toggle({ value, onChange }) {
  return (
    <div onClick={() => onChange(!value)} style={{
      width: 40, height: 22, borderRadius: 11,
      background: value ? T.primary : "#D1D5DB",
      position: "relative", cursor: "pointer", flexShrink: 0, transition: "background 0.15s",
    }}>
      <div style={{
        position: "absolute", top: 2.5, left: value ? 20.5 : 2.5,
        width: 17, height: 17, borderRadius: "50%", background: "#fff",
        boxShadow: "0 1px 2px rgba(15,23,42,0.25)", transition: "left 0.15s",
      }} />
    </div>
  );
}

/* Section card */
function Section({ icon, title, subtitle, children }) {
  return (
    <div style={{ background: T.card, borderRadius: 10, border: `1px solid ${T.line}`, overflow: "hidden" }}>
      <div style={{
        padding: "12px 16px", borderBottom: `1px solid ${T.line}`,
        display: "flex", alignItems: "center", gap: 10, background: T.bg,
      }}>
        <div style={{
          width: 30, height: 30, borderRadius: 7, background: "#fff", border: `1px solid ${T.line}`,
          display: "flex", alignItems: "center", justifyContent: "center", color: T.primary, flexShrink: 0,
        }}><Icon name={icon} size={15} /></div>
        <div>
          <p style={{ fontSize: 13, fontWeight: 600, color: T.ink, margin: 0 }}>{title}</p>
          {subtitle && <p style={{ fontSize: 11, color: T.muted, margin: "2px 0 0" }}>{subtitle}</p>}
        </div>
      </div>
      <div style={{ padding: "16px" }}>{children}</div>
    </div>
  );
}

function ToggleRow({ label, description, value, onChange }) {
  return (
    <div style={{ display: "flex", alignItems: "flex-start", justifyContent: "space-between", gap: 12 }}>
      <div style={{ flex: 1 }}>
        <p style={{ fontSize: 13, fontWeight: 600, color: T.ink, margin: "0 0 3px" }}>{label}</p>
        {description && <p style={{ fontSize: 11, color: T.muted, margin: 0, lineHeight: 1.5 }}>{description}</p>}
      </div>
      <Toggle value={value} onChange={onChange} />
    </div>
  );
}

function Callout({ tone, icon, children }) {
  const tones = {
    info:    { bg: T.infoSoft,    border: T.infoRing,    color: T.info    },
    warning: { bg: T.warningSoft, border: T.warningRing, color: T.warning },
    danger:  { bg: T.dangerSoft,  border: T.dangerRing,  color: T.danger  },
  };
  const c = tones[tone];
  return (
    <div style={{
      padding: "10px 12px", background: c.bg, borderRadius: 8, border: `1px solid ${c.border}`,
      display: "flex", gap: 8, alignItems: "flex-start",
    }}>
      <span style={{ color: c.color, flexShrink: 0, marginTop: 1 }}><Icon name={icon} size={14} /></span>
      <p style={{ fontSize: 11.5, color: c.color, margin: 0, lineHeight: 1.5 }}>{children}</p>
    </div>
  );
}

export default function Settings() {
  const [weeklyHours, setWeeklyHours] = useState(40);
  const [pdApproval, setPdApproval] = useState(true);
  const [emailNotif, setEmailNotif] = useState(true);
  const [pmNotif, setPmNotif] = useState(false);
  const [saved, setSaved] = useState(false);

  const handleSave = () => {
    setSaved(true);
    setTimeout(() => setSaved(false), 2500);
  };

  return (
    <div style={{ fontFamily: "'Inter',system-ui,sans-serif", background: T.bg, minHeight: "100vh", maxWidth: 420, margin: "0 auto" }}>
      <TopBar />

      <div style={{ padding: "16px 14px", display: "flex", flexDirection: "column", gap: 13 }}>

        <h1 style={{ fontSize: 20, fontWeight: 700, color: T.ink, margin: 0 }}>Settings</h1>

        {/* ── Time & Overtime ── */}
        <Section icon="clock" title="Time & overtime" subtitle="Configure weekly hour thresholds">
          <label style={{ fontSize: 12, fontWeight: 600, color: T.body, display: "block", marginBottom: 6 }}>
            Standard weekly hours <span style={{ color: T.danger }}>*</span>
          </label>
          <div style={{ position: "relative", marginBottom: 11 }}>
            <input
              type="number" value={weeklyHours}
              onChange={e => setWeeklyHours(Number(e.target.value))}
              min={1} max={80}
              style={{
                width: "100%", boxSizing: "border-box", padding: "9px 48px 9px 12px",
                border: `1px solid ${T.line}`, borderRadius: 8, fontSize: 13.5, fontWeight: 600,
                color: T.ink, outline: "none", background: "#fff", fontFamily: "inherit",
              }}
            />
            <span style={{ position: "absolute", right: 12, top: "50%", transform: "translateY(-50%)", fontSize: 11, color: T.muted, fontWeight: 500 }}>
              hrs/wk
            </span>
          </div>

          <p style={{ fontSize: 11, color: T.muted, margin: "0 0 8px" }}>Common presets</p>
          <div style={{ display: "flex", gap: 7, marginBottom: 12 }}>
            {[{ label: "35h", sub: "MY Public", val: 35 }, { label: "37.5h", sub: "UK", val: 37.5 }, { label: "40h", sub: "Standard", val: 40 }].map(({ label, sub, val }) => (
              <button key={val} onClick={() => setWeeklyHours(val)} style={{
                flex: 1, padding: "7px 4px",
                background: weeklyHours === val ? T.primary : "#fff",
                color: weeklyHours === val ? "#fff" : T.body,
                border: `1px solid ${weeklyHours === val ? T.primary : T.line}`,
                borderRadius: 7, cursor: "pointer", fontFamily: "inherit",
                display: "flex", flexDirection: "column", alignItems: "center", gap: 1,
              }}>
                <span style={{ fontSize: 11, fontWeight: 700 }}>{label}</span>
                <span style={{ fontSize: 9, opacity: 0.75 }}>{sub}</span>
              </button>
            ))}
          </div>

          <Callout tone="warning" icon="information-circle">
            Weeks exceeding <strong>{weeklyHours}h</strong> are counted as overtime and flagged in the dashboard.
          </Callout>
        </Section>

        {/* ── Approvals ── */}
        <Section icon="check-circle" title="Approvals" subtitle="Configure the timesheet sign-off workflow">
          <div style={{ display: "flex", flexDirection: "column", gap: 14 }}>
            <ToggleRow
              label="Require Project Director approval"
              description="Timesheets need both PM and PD sign-off before they are fully approved."
              value={pdApproval} onChange={setPdApproval}
            />
            {pdApproval && (
              <Callout tone="info" icon="information-circle">
                Approval flow: <strong>Submit → PM Review → PD Approval → Approved</strong>
              </Callout>
            )}
          </div>
        </Section>

        {/* ── Notifications ── */}
        <Section icon="bell" title="Notifications" subtitle="Control automated email alerts">
          <div style={{ display: "flex", flexDirection: "column", gap: 16 }}>
            <ToggleRow
              label="Email notifications"
              description="Sends email on submit, approval, and rejection. Requires Resend domain verification and a queue worker."
              value={emailNotif} onChange={setEmailNotif}
            />
            <div style={{ height: 1, background: T.line }} />
            <ToggleRow
              label="Notify PM on submission"
              description="Project Manager receives an email when a team member submits a timesheet."
              value={pmNotif} onChange={setPmNotif}
            />
            {!emailNotif && (
              <Callout tone="danger" icon="exclamation-triangle">
                Email notifications are disabled. No alerts will be sent.
              </Callout>
            )}
          </div>
        </Section>

        {/* Save button */}
        <button onClick={handleSave} style={{
          width: "100%", padding: "12px",
          background: saved ? T.success : T.primary,
          border: "none", borderRadius: 9, color: "#fff", fontSize: 13.5, fontWeight: 600,
          cursor: "pointer", fontFamily: "inherit",
          display: "flex", alignItems: "center", justifyContent: "center", gap: 7,
        }}>
          {saved && <Icon name="check" size={14} />}
          {saved ? "Settings saved" : "Save settings"}
        </button>

        <p style={{ fontSize: 11, color: T.muted, textAlign: "center", margin: "0 0 8px" }}>
          Changes apply immediately to all users
        </p>

      </div>

      {/* Save toast — top-right Filament notification style */}
      {saved && (
        <div style={{
          position: "fixed", top: 16, right: 16, left: 16, maxWidth: 388, margin: "0 auto",
          background: "#fff", borderRadius: 10, border: `1px solid ${T.line}`,
          borderLeft: `3px solid ${T.success}`,
          padding: "11px 14px", boxShadow: "0 8px 24px rgba(15,23,42,0.12)", zIndex: 300,
          display: "flex", alignItems: "center", gap: 10,
        }}>
          <span style={{ color: T.success, display: "flex" }}><Icon name="check-circle" size={16} /></span>
          <span style={{ fontSize: 12.5, color: T.ink, flex: 1 }}>Settings saved successfully</span>
          <button onClick={() => setSaved(false)} style={{ background: "none", border: "none", color: T.muted, cursor: "pointer", padding: 0, display: "flex" }}>
            <Icon name="x-mark" size={14} />
          </button>
        </div>
      )}
    </div>
  );
}
