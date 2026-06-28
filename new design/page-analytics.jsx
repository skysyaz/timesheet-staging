import { useState } from "react";
import {
  BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, Cell,
} from "recharts";

const T = {
  primary: "#1B3860", primaryDk: "#102648", primarySoft: "#EAF0F8", accent: "#8B1520",
  ink: "#0F172A", body: "#475569", muted: "#94A3B8",
  line: "#E2E8F0", bg: "#F8FAFC", card: "#FFFFFF",
  success: "#047857", warning: "#B45309", violet: "#6D28D9", info: "#1D4ED8",
};

const ICONS = {
  menu: <><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></>,
  "document-text": <><rect x="6" y="3.5" width="12" height="17" rx="1.5"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="13" y2="16"/></>,
  "arrow-down-tray": <><path d="M12 4v10"/><path d="M8 10.5l4 4 4-4"/><path d="M5 19h14"/></>,
  "clipboard-list": <><rect x="6" y="4.5" width="12" height="16" rx="1.5"/><rect x="9" y="3" width="6" height="3" rx="1"/><line x1="8.5" y1="10" x2="15.5" y2="10"/><line x1="8.5" y1="13.5" x2="15.5" y2="13.5"/></>,
  clock: <><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3.2 2"/></>,
  "chart-bar": <><line x1="5" y1="19" x2="5" y2="11"/><line x1="10.5" y1="19" x2="10.5" y2="7"/><line x1="16" y1="19" x2="16" y2="14"/><line x1="4" y1="19" x2="19" y2="19"/></>,
  "chevron-down": <path d="M6 9l6 6 6-6"/>,
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

function Btn({ children, variant = "secondary", icon, full, onClick, disabled }) {
  const styles = {
    primary:   { bg: T.primary, color: "#fff", border: T.primary },
    secondary: { bg: "#fff", color: T.body, border: T.line },
  };
  const v = styles[variant] || styles.secondary;
  return (
    <button onClick={onClick} disabled={disabled} style={{
      display: "inline-flex", alignItems: "center", justifyContent: "center", gap: 6,
      background: disabled ? T.muted : v.bg, color: v.color, border: `1px solid ${disabled ? T.muted : v.border}`,
      borderRadius: 8, padding: "8px 13px", fontSize: 12, fontWeight: 600,
      cursor: disabled ? "default" : "pointer", width: full ? "100%" : "auto", fontFamily: "inherit",
    }}>
      {icon && <Icon name={icon} size={13} />}
      {children}
    </button>
  );
}

const fieldBox = {
  width: "100%", boxSizing: "border-box", padding: "9px 10px",
  border: `1px solid ${T.line}`, borderRadius: 8, fontSize: 12.5,
  color: T.ink, background: "#fff", outline: "none", fontFamily: "inherit",
};

function SelectField({ label, value, onChange, options }) {
  return (
    <div style={{ flex: 1 }}>
      <label style={{ fontSize: 11, fontWeight: 600, color: T.body, display: "block", marginBottom: 5 }}>{label}</label>
      <div style={{ position: "relative" }}>
        <select
          value={value} onChange={e => onChange(e.target.value)}
          style={{ ...fieldBox, appearance: "none", paddingRight: 26 }}
        >
          {options.map(o => <option key={o.value} value={o.value}>{o.label}</option>)}
        </select>
        <span style={{ position: "absolute", right: 9, top: "50%", transform: "translateY(-50%)", color: T.muted, pointerEvents: "none" }}>
          <Icon name="chevron-down" size={13} />
        </span>
      </div>
    </div>
  );
}

function DateField({ label, value, onChange }) {
  return (
    <div style={{ flex: 1 }}>
      <label style={{ fontSize: 11, fontWeight: 600, color: T.body, display: "block", marginBottom: 5 }}>{label}</label>
      <input type="date" value={value} onChange={e => onChange(e.target.value)} style={fieldBox} />
    </div>
  );
}

const PROJECT_DATA = [
  { project: "DASH",     hours: 6.0, share: 100.0, color: T.primary },
  { project: "TCS",      hours: 0,   share: 0,     color: T.success },
  { project: "HSWIM",    hours: 0,   share: 0,     color: T.warning },
  { project: "DataSync", hours: 0,   share: 0,     color: T.violet  },
];
const CHART_DATA = PROJECT_DATA.map(p => ({ name: p.project, hours: p.hours }));

export default function Analytics() {
  const [groupBy, setGroupBy] = useState("project");
  const [fromDate, setFromDate] = useState("2026-06-01");
  const [toDate, setToDate] = useState("2026-06-30");
  const [project, setProject] = useState("all");
  const [exporting, setExporting] = useState(false);

  const totalHours = PROJECT_DATA.reduce((s, p) => s + p.hours, 0);
  const entryCount = PROJECT_DATA.filter(p => p.hours > 0).length;
  const avgHours = entryCount > 0 ? (totalHours / entryCount).toFixed(1) : "0.0";

  const handleExport = () => {
    setExporting(true);
    setTimeout(() => setExporting(false), 1800);
  };

  return (
    <div style={{ fontFamily: "'Inter',system-ui,sans-serif", background: T.bg, minHeight: "100vh", maxWidth: 420, margin: "0 auto" }}>
      <TopBar />

      <div style={{ padding: "16px 14px", display: "flex", flexDirection: "column", gap: 14 }}>

        {/* Page header */}
        <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between" }}>
          <h1 style={{ fontSize: 19, fontWeight: 700, color: T.ink, margin: 0 }}>Analytics &amp; Reports</h1>
          <Btn variant="secondary" icon="arrow-down-tray" onClick={handleExport} disabled={exporting}>
            {exporting ? "Exporting…" : "Export PDF"}
          </Btn>
        </div>

        {/* Stat cards */}
        <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr 1fr", gap: 8 }}>
          {[
            { label: "Entries",     value: entryCount,        icon: "clipboard-list", tone: T.info },
            { label: "Total Hours", value: `${totalHours}h`,  icon: "clock",           tone: T.success },
            { label: "Average",     value: `${avgHours}h`,    icon: "chart-bar",       tone: T.violet },
          ].map(({ label, value, icon, tone }) => (
            <div key={label} style={{
              background: T.card, border: `1px solid ${T.line}`, borderRadius: 10,
              padding: "11px 8px", textAlign: "center", borderBottom: `3px solid ${tone}`,
            }}>
              <div style={{ display: "flex", justifyContent: "center", marginBottom: 6, color: tone }}>
                <Icon name={icon} size={16} />
              </div>
              <p style={{ fontSize: 9.5, color: T.muted, margin: "0 0 3px", fontWeight: 600, textTransform: "uppercase", letterSpacing: "0.04em" }}>{label}</p>
              <p style={{ fontSize: 17, fontWeight: 700, color: T.ink, margin: 0 }}>{value}</p>
            </div>
          ))}
        </div>

        {/* Filters */}
        <div style={{ background: T.card, borderRadius: 10, border: `1px solid ${T.line}`, padding: "14px" }}>
          <p style={{ fontSize: 13, fontWeight: 600, color: T.ink, margin: "0 0 2px" }}>Report filters</p>
          <p style={{ fontSize: 11, color: T.muted, margin: "0 0 13px" }}>Refine by date range, project, and grouping</p>

          <div style={{ display: "flex", gap: 9, marginBottom: 9 }}>
            <SelectField
              label="Group by" value={groupBy} onChange={setGroupBy}
              options={[
                { value: "project", label: "By Project" },
                { value: "user",    label: "By User" },
                { value: "week",    label: "By Week" },
              ]}
            />
            <SelectField
              label="Project" value={project} onChange={setProject}
              options={[
                { value: "all",  label: "All Projects" },
                { value: "dash", label: "DASH" },
                { value: "tcs",  label: "TCS" },
                { value: "hw",   label: "HSWIM" },
              ]}
            />
          </div>
          <div style={{ display: "flex", gap: 9 }}>
            <DateField label="From date" value={fromDate} onChange={setFromDate} />
            <DateField label="To date" value={toDate} onChange={setToDate} />
          </div>

          <div style={{ marginTop: 13 }}>
            <Btn variant="primary" full>Apply filters</Btn>
          </div>
        </div>

        {/* Bar chart */}
        <div style={{ background: T.card, borderRadius: 10, border: `1px solid ${T.line}`, padding: "14px" }}>
          <p style={{ fontSize: 13, fontWeight: 600, color: T.ink, margin: "0 0 2px" }}>Hours by project</p>
          <p style={{ fontSize: 11, color: T.muted, margin: "0 0 12px" }}>
            {entryCount} {entryCount === 1 ? "entry" : "entries"} · {totalHours}h total
          </p>
          <ResponsiveContainer width="100%" height={130}>
            <BarChart data={CHART_DATA} barSize={22} margin={{ top: 4, right: 4, left: -10, bottom: 0 }}>
              <XAxis dataKey="name" tick={{ fontSize: 10, fill: T.muted }} axisLine={false} tickLine={false} />
              <YAxis tick={{ fontSize: 10, fill: T.muted }} axisLine={false} tickLine={false} />
              <Tooltip
                contentStyle={{ borderRadius: 8, border: `1px solid ${T.line}`, fontSize: 11, boxShadow: "0 4px 12px rgba(15,23,42,0.08)" }}
                cursor={{ fill: T.bg }}
                formatter={(val) => [`${val}h`, "Hours"]}
              />
              <Bar dataKey="hours" radius={[4, 4, 0, 0]}>
                {CHART_DATA.map((_, i) => (
                  <Cell key={i} fill={PROJECT_DATA[i % PROJECT_DATA.length].color} />
                ))}
              </Bar>
            </BarChart>
          </ResponsiveContainer>
        </div>

        {/* Distribution table */}
        <div style={{ background: T.card, borderRadius: 10, border: `1px solid ${T.line}`, padding: "14px" }}>
          <p style={{ fontSize: 13, fontWeight: 600, color: T.ink, margin: "0 0 12px" }}>Distribution</p>

          <div style={{
            display: "grid", gridTemplateColumns: "1fr 60px 60px",
            gap: 8, paddingBottom: 8, borderBottom: `1px solid ${T.line}`, marginBottom: 4,
          }}>
            {["PROJECT", "HOURS", "SHARE"].map(h => (
              <span key={h} style={{ fontSize: 9.5, fontWeight: 700, color: T.muted, letterSpacing: "0.06em" }}>{h}</span>
            ))}
          </div>

          {PROJECT_DATA.map((p, i) => (
            <div key={p.project} style={{
              padding: "10px 0",
              borderBottom: i < PROJECT_DATA.length - 1 ? `1px solid ${T.bg}` : "none",
            }}>
              <div style={{ display: "grid", gridTemplateColumns: "1fr 60px 60px", gap: 8, alignItems: "center", marginBottom: 6 }}>
                <div style={{ display: "flex", alignItems: "center", gap: 7 }}>
                  <span style={{ width: 8, height: 8, borderRadius: "50%", background: p.color, flexShrink: 0 }} />
                  <span style={{ fontSize: 12, fontWeight: 600, color: T.ink }}>{p.project}</span>
                </div>
                <span style={{ fontSize: 12, fontWeight: 700, color: T.ink }}>{p.hours}h</span>
                <span style={{ fontSize: 12, color: T.body }}>{p.share.toFixed(1)}%</span>
              </div>
              <div style={{ height: 4, background: T.bg, borderRadius: 2, overflow: "hidden" }}>
                <div style={{ height: "100%", width: `${p.share}%`, background: p.color, borderRadius: 2 }} />
              </div>
            </div>
          ))}

          <div style={{
            display: "grid", gridTemplateColumns: "1fr 60px 60px",
            gap: 8, paddingTop: 10, borderTop: `2px solid ${T.line}`, marginTop: 4,
          }}>
            <span style={{ fontSize: 12, fontWeight: 700, color: T.ink }}>Total</span>
            <span style={{ fontSize: 12, fontWeight: 700, color: T.primary }}>{totalHours}h</span>
            <span style={{ fontSize: 12, fontWeight: 700, color: T.ink }}>100%</span>
          </div>
        </div>

      </div>
    </div>
  );
}
