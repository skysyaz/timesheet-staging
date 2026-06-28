import { useState } from "react";
import {
  BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer,
  PieChart, Pie, Cell, Legend
} from "recharts";

/* ───────────────────────── Design tokens ─────────────────────────
   A Filament-style slate palette: flat surfaces, hairline borders,
   semantic colors used sparingly (text + a thin accent border),
   never as full pastel card fills. */
const T = {
  primary: "#1B3860", primaryDk: "#102648", primarySoft: "#EAF0F8",
  accent: "#8B1520",
  ink: "#0F172A", body: "#475569", muted: "#94A3B8",
  line: "#E2E8F0", bg: "#F8FAFC", card: "#FFFFFF",
  success: "#047857", successSoft: "#ECFDF5", successRing: "#A7F3D0", successDot: "#10B981",
  warning: "#B45309", warningSoft: "#FFFBEB", warningRing: "#FDE68A", warningDot: "#F59E0B",
  danger: "#BE123C", dangerSoft: "#FFF1F2", dangerRing: "#FECDD3", dangerDot: "#F43F5E",
  info: "#1D4ED8", infoSoft: "#EFF6FF", infoRing: "#BFDBFE", infoDot: "#3B82F6",
};

/* ───────────────────────── Icons ─────────────────────────
   Hand-built line icons (24x24, stroke-based) so nothing reads as
   a stock emoji. Only the set this page actually uses. */
const ICONS = {
  menu: <><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></>,
  "chevron-right": <path d="M9 6l6 6-6 6"/>,
  "check-circle": <><circle cx="12" cy="12" r="8.5"/><path d="M8.3 12.3l2.4 2.4 4.6-5.4"/></>,
  "arrow-down-tray": <><path d="M12 4v10"/><path d="M8 10.5l4 4 4-4"/><path d="M5 19h14"/></>,
  plus: <><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></>,
  clock: <><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3.2 2"/></>,
  "exclamation-triangle": <><path d="M12 4.5L21 19H3L12 4.5z"/><line x1="12" y1="10" x2="12" y2="14.3"/><circle cx="12" cy="17" r="0.6" fill="currentColor" stroke="none"/></>,
};
function Icon({ name, size = 16, color = "currentColor", strokeWidth = 1.75 }) {
  return (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke={color}
      strokeWidth={strokeWidth} strokeLinecap="round" strokeLinejoin="round"
      style={{ display: "block", flexShrink: 0 }}>
      {ICONS[name]}
    </svg>
  );
}

/* ───────────────────────── Brand mark ───────────────────────── */
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

/* ───────────────────────── Shared primitives ───────────────────────── */
function TopBar() {
  return (
    <div style={{
      background: T.card, borderBottom: `1px solid ${T.line}`,
      padding: "11px 16px", display: "flex", alignItems: "center",
      justifyContent: "space-between", position: "sticky", top: 0, zIndex: 100,
    }}>
      <div style={{ display: "flex", alignItems: "center", gap: 12 }}>
        <span style={{ color: T.body, display: "flex" }}><Icon name="menu" size={19} /></span>
        <div style={{ width: 1, height: 18, background: T.line }} />
        <QuatrizLogo height={22} />
      </div>
      <div style={{
        width: 30, height: 30, borderRadius: "50%",
        background: T.primarySoft, color: T.primary,
        display: "flex", alignItems: "center", justifyContent: "center",
        fontSize: 12, fontWeight: 700, border: `1px solid ${T.line}`,
      }}>A</div>
    </div>
  );
}

function Btn({ children, variant = "secondary", icon, full, small, onClick }) {
  const styles = {
    primary:   { bg: T.primary, color: "#fff", border: T.primary },
    secondary: { bg: "#fff", color: T.body, border: T.line },
  };
  const v = styles[variant] || styles.secondary;
  return (
    <button onClick={onClick} style={{
      display: "inline-flex", alignItems: "center", justifyContent: "center", gap: 6,
      background: v.bg, color: v.color, border: `1px solid ${v.border}`,
      borderRadius: 8, padding: small ? "7px 10px" : "9px 14px",
      fontSize: small ? 11.5 : 12.5, fontWeight: 600, cursor: "pointer",
      width: full ? "100%" : "auto", fontFamily: "inherit",
    }}>
      {icon && <Icon name={icon} size={small ? 13 : 14} />}
      {children}
    </button>
  );
}

function Badge({ label, tone = "gray", dot }) {
  const tones = {
    success: { bg: T.successSoft, color: T.success, ring: T.successRing, dot: T.successDot },
    warning: { bg: T.warningSoft, color: T.warning, ring: T.warningRing, dot: T.warningDot },
    danger:  { bg: T.dangerSoft,  color: T.danger,  ring: T.dangerRing,  dot: T.dangerDot  },
    info:    { bg: T.infoSoft,    color: T.info,    ring: T.infoRing,    dot: T.infoDot    },
    gray:    { bg: "#F1F5F9",     color: T.body,    ring: T.line,        dot: T.muted      },
  };
  const c = tones[tone] || tones.gray;
  return (
    <span style={{
      display: "inline-flex", alignItems: "center", gap: 5,
      background: c.bg, color: c.color, border: `1px solid ${c.ring}`,
      borderRadius: 6, padding: "2px 8px", fontSize: 10.5, fontWeight: 600, whiteSpace: "nowrap",
    }}>
      {dot && <span style={{ width: 5, height: 5, borderRadius: "50%", background: c.dot, flexShrink: 0 }} />}
      {label}
    </span>
  );
}

function Card({ children, style }) {
  return (
    <div style={{
      background: T.card, border: `1px solid ${T.line}`, borderRadius: 10,
      padding: "14px", ...style,
    }}>{children}</div>
  );
}

/* ───────────────────────── Data ───────────────────────── */
const barData = [
  { day: "Mon", hours: 2.8 }, { day: "Tue", hours: 2.9 },
  { day: "Wed", hours: 0 }, { day: "Thu", hours: 0 },
  { day: "Fri", hours: 0 }, { day: "Sat", hours: 0 }, { day: "Sun", hours: 0 },
];
const pieData = [{ name: "DASH", value: 6 }];
const PIE_COLORS = [T.primary];

const recentTimesheets = [
  { name: "Syazwan", project: "DASH",     hours: 6,  status: "Approved" },
  { name: "Ahmad",   project: "TCS",      hours: 40, status: "Pending"  },
  { name: "Farah",   project: "HSWIM",    hours: 38, status: "Pending"  },
  { name: "Haziq",   project: "DataSync", hours: 42, status: "Rejected" },
];
const STATUS_TONE = { Approved: "success", Pending: "warning", Rejected: "danger" };

/* ───────────────────────── Stat widget (Filament stats-overview style) ───────────────────────── */
function StatCard({ label, value, description, tone, icon }) {
  const toneColor = { info: T.info, success: T.success, warning: T.warning, danger: T.danger }[tone];
  return (
    <div style={{
      background: T.card, border: `1px solid ${T.line}`, borderBottom: `3px solid ${toneColor}`,
      borderRadius: 10, padding: "13px 14px 11px",
      display: "flex", flexDirection: "column", gap: 5,
    }}>
      <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between" }}>
        <span style={{ fontSize: 10.5, color: T.muted, fontWeight: 600, textTransform: "uppercase", letterSpacing: "0.04em" }}>
          {label}
        </span>
        <Icon name={icon} size={14} color={T.muted} />
      </div>
      <span style={{ fontSize: 23, fontWeight: 700, color: T.ink, lineHeight: 1.1 }}>{value}</span>
      <span style={{ fontSize: 11, color: toneColor, fontWeight: 500 }}>{description}</span>
    </div>
  );
}

/* ───────────────────────── Dashboard ───────────────────────── */
export default function Dashboard() {
  const [tab, setTab] = useState("week");

  return (
    <div style={{
      fontFamily: "'Inter',system-ui,sans-serif",
      background: T.bg, minHeight: "100vh", maxWidth: 420, margin: "0 auto",
    }}>
      <TopBar />

      <div style={{ padding: "16px 14px", display: "flex", flexDirection: "column", gap: 14 }}>

        {/* ── Welcome header ── */}
        <div style={{ display: "flex", alignItems: "flex-start", justifyContent: "space-between", gap: 10 }}>
          <div>
            <p style={{ fontSize: 11, color: T.muted, margin: "0 0 3px", fontWeight: 500 }}>
              Saturday, 27 June 2026
            </p>
            <h1 style={{ fontSize: 19, fontWeight: 700, color: T.ink, margin: 0, lineHeight: 1.3 }}>
              Good afternoon, Administrator
            </h1>
            <p style={{ fontSize: 12, color: T.body, margin: "4px 0 0" }}>
              4 timesheets logged this week
            </p>
          </div>
          <button style={{
            background: "none", border: "none", cursor: "pointer", padding: "4px 0",
            color: T.primary, fontSize: 12, fontWeight: 600,
            display: "flex", alignItems: "center", gap: 3, whiteSpace: "nowrap",
          }}>
            View all <Icon name="chevron-right" size={13} />
          </button>
        </div>

        {/* ── Stats 2×2 ── */}
        <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 10 }}>
          <StatCard label="Total Hours"    value="6.0h" description="Up from last week" tone="info"    icon="clock" />
          <StatCard label="Approved"       value="1"    description="Fully approved"    tone="success" icon="check-circle" />
          <StatCard label="Pending Review" value="0"    description="Awaiting approval" tone="warning" icon="clock" />
          <StatCard label="Overtime Weeks" value="0"    description="Exceeds 40h / wk"   tone="danger"  icon="exclamation-triangle" />
        </div>

        {/* ── Quick actions ── */}
        <Card>
          <p style={{ fontSize: 11, fontWeight: 600, color: T.body, margin: "0 0 10px", textTransform: "uppercase", letterSpacing: "0.05em" }}>
            Quick actions
          </p>
          <div style={{ display: "flex", gap: 8 }}>
            <Btn variant="secondary" icon="check-circle" full small>Approve</Btn>
            <Btn variant="secondary" icon="arrow-down-tray" full small>Export</Btn>
            <Btn variant="primary" icon="plus" full small>New entry</Btn>
          </div>
        </Card>

        {/* ── Hours by Day ── */}
        <Card>
          <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 10 }}>
            <div>
              <p style={{ fontSize: 13, fontWeight: 600, color: T.ink, margin: 0 }}>Hours by day</p>
              <p style={{ fontSize: 11, color: T.muted, margin: "2px 0 0" }}>This week</p>
            </div>
            <div style={{ display: "flex", gap: 2, background: T.bg, borderRadius: 8, padding: 2 }}>
              {["week", "month"].map(t => (
                <button key={t} onClick={() => setTab(t)} style={{
                  padding: "4px 10px", borderRadius: 6, border: "none",
                  background: tab === t ? "#fff" : "transparent",
                  boxShadow: tab === t ? "0 1px 2px rgba(15,23,42,0.08)" : "none",
                  color: tab === t ? T.ink : T.muted,
                  fontSize: 10.5, fontWeight: 600, cursor: "pointer",
                }}>
                  {t[0].toUpperCase() + t.slice(1)}
                </button>
              ))}
            </div>
          </div>
          <ResponsiveContainer width="100%" height={130}>
            <BarChart data={barData} barSize={17}>
              <XAxis dataKey="day" tick={{ fontSize: 10, fill: T.muted }} axisLine={false} tickLine={false} />
              <YAxis tick={{ fontSize: 10, fill: T.muted }} axisLine={false} tickLine={false} width={20} />
              <Tooltip
                contentStyle={{ borderRadius: 8, border: `1px solid ${T.line}`, fontSize: 11, boxShadow: "0 4px 12px rgba(15,23,42,0.08)" }}
                cursor={{ fill: T.bg }}
              />
              <Bar dataKey="hours" fill={T.primary} radius={[3, 3, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </Card>

        {/* ── Hours by Project ── */}
        <Card>
          <p style={{ fontSize: 13, fontWeight: 600, color: T.ink, margin: "0 0 2px" }}>Hours by project</p>
          <p style={{ fontSize: 11, color: T.muted, margin: "0 0 8px" }}>Active projects this month</p>
          <ResponsiveContainer width="100%" height={150}>
            <PieChart>
              <Pie data={pieData} cx="50%" cy="50%" innerRadius={44} outerRadius={62} dataKey="value" paddingAngle={3}>
                {pieData.map((_, i) => <Cell key={i} fill={PIE_COLORS[i % PIE_COLORS.length]} />)}
              </Pie>
              <Legend iconType="circle" iconSize={8} wrapperStyle={{ fontSize: 11 }} />
              <Tooltip contentStyle={{ borderRadius: 8, fontSize: 11, border: `1px solid ${T.line}` }} />
            </PieChart>
          </ResponsiveContainer>
        </Card>

        {/* ── Recent Timesheets (table style, not stacked cards) ── */}
        <Card style={{ padding: "14px 0 4px" }}>
          <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 8, padding: "0 14px" }}>
            <div>
              <p style={{ fontSize: 13, fontWeight: 600, color: T.ink, margin: 0 }}>Recent timesheets</p>
              <p style={{ fontSize: 11, color: T.muted, margin: "2px 0 0" }}>Latest submissions</p>
            </div>
            <button style={{ background: "none", border: "none", cursor: "pointer", color: T.primary, fontSize: 11.5, fontWeight: 600, padding: 0 }}>
              See all
            </button>
          </div>
          <div style={{
            display: "grid", gridTemplateColumns: "1fr auto auto", gap: 8,
            padding: "6px 14px", borderTop: `1px solid ${T.line}`, borderBottom: `1px solid ${T.line}`,
            background: T.bg,
          }}>
            <span style={{ fontSize: 9.5, fontWeight: 700, color: T.muted, letterSpacing: "0.06em" }}>USER</span>
            <span style={{ fontSize: 9.5, fontWeight: 700, color: T.muted, letterSpacing: "0.06em" }}>HOURS</span>
            <span style={{ fontSize: 9.5, fontWeight: 700, color: T.muted, letterSpacing: "0.06em" }}>STATUS</span>
          </div>
          {recentTimesheets.map((r, i) => (
            <div key={i} style={{
              display: "grid", gridTemplateColumns: "1fr auto auto", gap: 8, alignItems: "center",
              padding: "10px 14px",
              borderBottom: i < recentTimesheets.length - 1 ? `1px solid ${T.line}` : "none",
            }}>
              <div style={{ display: "flex", alignItems: "center", gap: 8, minWidth: 0 }}>
                <div style={{
                  width: 26, height: 26, borderRadius: "50%", flexShrink: 0,
                  background: T.primarySoft, color: T.primary,
                  display: "flex", alignItems: "center", justifyContent: "center",
                  fontSize: 11, fontWeight: 700,
                }}>{r.name[0]}</div>
                <div style={{ minWidth: 0 }}>
                  <p style={{ fontSize: 12, fontWeight: 600, color: T.ink, margin: 0, whiteSpace: "nowrap" }}>{r.name}</p>
                  <p style={{ fontSize: 10, color: T.muted, margin: 0 }}>{r.project}</p>
                </div>
              </div>
              <span style={{ fontSize: 12, fontWeight: 600, color: T.ink }}>{r.hours}h</span>
              <Badge label={r.status} tone={STATUS_TONE[r.status]} dot />
            </div>
          ))}
        </Card>

        {/* ── Footer brand ── */}
        <div style={{ display: "flex", justifyContent: "center", alignItems: "center", padding: "8px 0 4px", gap: 6, opacity: 0.5 }}>
          <QuatrizLogo height={16} />
          <span style={{ fontSize: 9, color: T.muted, fontWeight: 500 }}>Timesheet System</span>
        </div>

      </div>
    </div>
  );
}
