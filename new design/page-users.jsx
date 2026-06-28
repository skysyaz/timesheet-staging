import { useState } from "react";

const T = {
  primary: "#1B3860", primaryDk: "#102648", primarySoft: "#EAF0F8", accent: "#8B1520",
  ink: "#0F172A", body: "#475569", muted: "#94A3B8",
  line: "#E2E8F0", bg: "#F8FAFC", card: "#FFFFFF",
  success: "#047857", successSoft: "#ECFDF5", successRing: "#A7F3D0",
  warning: "#B45309", warningSoft: "#FFFBEB", warningRing: "#FDE68A",
  danger: "#BE123C", dangerSoft: "#FFF1F2", dangerRing: "#FECDD3",
  info: "#1D4ED8", infoSoft: "#EFF6FF", infoRing: "#BFDBFE",
  violet: "#6D28D9", violetSoft: "#F5F3FF", violetRing: "#DDD6FE",
};

const ICONS = {
  menu: <><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></>,
  search: <><circle cx="10.5" cy="10.5" r="6.5"/><line x1="15.3" y1="15.3" x2="20.5" y2="20.5"/></>,
  plus: <><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></>,
  pencil: <><path d="M3.5 20.5l3.6-1 11.5-11.5a1.4 1.4 0 000-2l-1.6-1.6a1.4 1.4 0 00-2 0L3.5 16l-1 4.5z"/><line x1="14.5" y1="6.5" x2="18.1" y2="10.1"/></>,
  trash: <><line x1="5" y1="7" x2="19" y2="7"/><path d="M9 7V5a2 2 0 012-2h2a2 2 0 012 2v2"/><path d="M7 7l1 12.5a2 2 0 002 2h4a2 2 0 002-2L17 7"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></>,
  key: <><circle cx="8" cy="15.5" r="3.5"/><path d="M10.5 13L19 4.5"/><path d="M15.8 7.2l2 2"/><path d="M13.1 9.9l2 2"/></>,
  "chevron-right": <path d="M9 6l6 6-6 6"/>,
  "exclamation-triangle": <><path d="M12 4.5L21 19H3L12 4.5z"/><line x1="12" y1="10" x2="12" y2="14.3"/><circle cx="12" cy="17" r="0.6" fill="currentColor" stroke="none"/></>,
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

function Btn({ children, variant = "secondary", icon, onClick }) {
  const styles = {
    primary:   { bg: T.primary, color: "#fff", border: T.primary },
    secondary: { bg: "#fff", color: T.body, border: T.line },
    danger:    { bg: T.danger, color: "#fff", border: T.danger },
  };
  const v = styles[variant] || styles.secondary;
  return (
    <button onClick={onClick} style={{
      display: "inline-flex", alignItems: "center", justifyContent: "center", gap: 6,
      background: v.bg, color: v.color, border: `1px solid ${v.border}`,
      borderRadius: 8, padding: "9px 14px", fontSize: 12.5, fontWeight: 600,
      cursor: "pointer", fontFamily: "inherit",
    }}>
      {icon && <Icon name={icon} size={13} />}
      {children}
    </button>
  );
}

function IconBtn({ icon, onClick, tone = "default" }) {
  const tones = {
    default: { color: T.body },
    danger:  { color: T.danger },
  };
  const c = tones[tone];
  return (
    <button onClick={onClick} style={{
      width: 28, height: 28, borderRadius: 7, border: `1px solid ${T.line}`, background: "#fff",
      display: "flex", alignItems: "center", justifyContent: "center", color: c.color, cursor: "pointer",
    }}><Icon name={icon} size={14} /></button>
  );
}

function Badge({ label, tone = "gray" }) {
  const tones = {
    danger: { bg: T.dangerSoft, color: T.danger, ring: T.dangerRing },
    info:   { bg: T.infoSoft,   color: T.info,   ring: T.infoRing   },
    warning:{ bg: T.warningSoft,color: T.warning,ring: T.warningRing},
    violet: { bg: T.violetSoft, color: T.violet, ring: T.violetRing },
    gray:   { bg: "#F1F5F9",    color: T.body,   ring: T.line       },
  };
  const c = tones[tone] || tones.gray;
  return (
    <span style={{
      background: c.bg, color: c.color, border: `1px solid ${c.ring}`,
      fontSize: 10.5, fontWeight: 600, padding: "2px 9px", borderRadius: 6, whiteSpace: "nowrap",
    }}>{label}</span>
  );
}

const ROLE_TONE = { Admin: "danger", Employee: "info", "Project Manager": "warning", "Project Director": "violet" };

const USERS = [
  { id: 1, name: "Administrator", initials: "A", email: "syazwan91@gmail.com",            role: "Admin",            color: "#3B82F6" },
  { id: 2, name: "Syazwan",       initials: "S", email: "syazwan.zulkifli@quatriz.com.my", role: "Employee",         color: "#10B981" },
  { id: 3, name: "Tareq",         initials: "T", email: "tareq.rasyad@quatriz.com.my",     role: "Employee",         color: "#10B981" },
  { id: 4, name: "Ainie",         initials: "A", email: "ainie@quatriz.com.my",            role: "Project Manager",  color: "#10B981" },
];

export default function Users() {
  const [search, setSearch] = useState("");
  const [confirm, setConfirm] = useState(null);
  const [resetMsg, setResetMsg] = useState(null);

  const filtered = USERS.filter(u =>
    u.name.toLowerCase().includes(search.toLowerCase()) ||
    u.email.toLowerCase().includes(search.toLowerCase()) ||
    u.role.toLowerCase().includes(search.toLowerCase())
  );

  const roleCounts = USERS.reduce((acc, u) => { acc[u.role] = (acc[u.role] || 0) + 1; return acc; }, {});

  return (
    <div style={{ fontFamily: "'Inter',system-ui,sans-serif", background: T.bg, minHeight: "100vh", maxWidth: 420, margin: "0 auto" }}>
      <TopBar />

      <div style={{ padding: "16px 14px", display: "flex", flexDirection: "column", gap: 13 }}>

        {/* Page header */}
        <div>
          <div style={{ display: "flex", alignItems: "center", gap: 5, marginBottom: 5 }}>
            <span style={{ fontSize: 11, color: T.primary, fontWeight: 600 }}>Users</span>
            <Icon name="chevron-right" size={11} color={T.muted} />
            <span style={{ fontSize: 11, color: T.muted }}>List</span>
          </div>
          <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between" }}>
            <h1 style={{ fontSize: 20, fontWeight: 700, color: T.ink, margin: 0 }}>Users</h1>
            <Btn variant="primary" icon="plus">New user</Btn>
          </div>
        </div>

        {/* Search */}
        <div style={{ position: "relative" }}>
          <span style={{ position: "absolute", left: 11, top: "50%", transform: "translateY(-50%)", color: T.muted, display: "flex" }}>
            <Icon name="search" size={15} />
          </span>
          <input
            value={search} onChange={e => setSearch(e.target.value)}
            placeholder="Search by name, email or role…"
            style={{
              width: "100%", boxSizing: "border-box", padding: "10px 12px 10px 34px",
              border: `1px solid ${T.line}`, borderRadius: 8, fontSize: 12.5, outline: "none",
              background: "#fff", fontFamily: "inherit", color: T.ink,
            }}
          />
        </div>

        {/* Role breakdown strip */}
        <div style={{ display: "flex", gap: 7 }}>
          {Object.entries(roleCounts).map(([role, count]) => (
            <div key={role} style={{
              flex: 1, background: T.card, border: `1px solid ${T.line}`,
              borderRadius: 8, padding: "8px 4px", textAlign: "center",
            }}>
              <p style={{ fontSize: 16, fontWeight: 700, color: T.ink, margin: 0 }}>{count}</p>
              <p style={{ fontSize: 9.5, color: T.muted, margin: 0, fontWeight: 600 }}>
                {role === "Project Manager" ? "PM" : role === "Project Director" ? "PD" : role}
              </p>
            </div>
          ))}
        </div>

        {/* User list */}
        <div style={{ background: T.card, border: `1px solid ${T.line}`, borderRadius: 10, overflow: "hidden" }}>
          {filtered.map((u, i) => (
            <div key={u.id} style={{
              padding: "13px 14px",
              borderBottom: i < filtered.length - 1 ? `1px solid ${T.line}` : "none",
            }}>
              <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", marginBottom: 9, gap: 8 }}>
                <div style={{ display: "flex", alignItems: "center", gap: 10, minWidth: 0 }}>
                  <div style={{
                    width: 34, height: 34, borderRadius: "50%", flexShrink: 0,
                    background: T.primarySoft, color: T.primary,
                    display: "flex", alignItems: "center", justifyContent: "center",
                    fontSize: 13, fontWeight: 700,
                    boxShadow: `0 0 0 2px #fff, 0 0 0 3px ${u.color}55`,
                  }}>{u.initials}</div>
                  <div style={{ minWidth: 0 }}>
                    <p style={{ fontSize: 13, fontWeight: 600, color: T.ink, margin: 0, whiteSpace: "nowrap", overflow: "hidden", textOverflow: "ellipsis" }}>{u.name}</p>
                    <p style={{ fontSize: 11, color: T.muted, margin: "1px 0 0", whiteSpace: "nowrap", overflow: "hidden", textOverflow: "ellipsis" }}>{u.email}</p>
                  </div>
                </div>
                <Badge label={u.role} tone={ROLE_TONE[u.role]} />
              </div>

              <div style={{
                display: "flex", alignItems: "center", gap: 7,
                padding: "8px 0", borderTop: `1px solid ${T.bg}`, borderBottom: `1px solid ${T.bg}`,
                marginBottom: 10, fontSize: 11, color: T.muted,
              }}>
                <span>Calendar color</span>
                <span style={{ width: 14, height: 14, borderRadius: 4, background: u.color, border: `1px solid ${T.line}`, display: "inline-block" }} />
                <span style={{ fontFamily: "monospace", fontSize: 10.5 }}>{u.color}</span>
              </div>

              <div style={{ display: "flex", justifyContent: "flex-end", gap: 6 }}>
                <IconBtn icon="key" onClick={() => setResetMsg(u.name)} />
                <IconBtn icon="pencil" />
                <IconBtn icon="trash" tone="danger" onClick={() => setConfirm(u.id)} />
              </div>
            </div>
          ))}
        </div>

        {/* Pagination */}
        <div style={{
          display: "flex", alignItems: "center", justifyContent: "space-between",
          background: T.card, borderRadius: 8, border: `1px solid ${T.line}`, padding: "9px 14px",
        }}>
          <span style={{ fontSize: 11, color: T.muted }}>Showing {filtered.length} of {USERS.length}</span>
          <div style={{ display: "flex", alignItems: "center", gap: 8 }}>
            <span style={{ fontSize: 11, color: T.body }}>Per page</span>
            <select style={{ border: `1px solid ${T.line}`, borderRadius: 6, fontSize: 11, padding: "3px 6px", color: T.body, fontFamily: "inherit" }}>
              <option>10</option><option>25</option>
            </select>
          </div>
        </div>
      </div>

      {/* Reset password toast — top-right, Filament notification style */}
      {resetMsg && (
        <div style={{
          position: "fixed", top: 16, right: 16, left: 16, maxWidth: 388, margin: "0 auto",
          background: "#fff", borderRadius: 10, border: `1px solid ${T.line}`,
          borderLeft: `3px solid ${T.primary}`,
          padding: "11px 14px", boxShadow: "0 8px 24px rgba(15,23,42,0.12)", zIndex: 300,
          display: "flex", alignItems: "flex-start", gap: 10,
        }}>
          <span style={{ color: T.primary, marginTop: 1 }}><Icon name="key" size={15} /></span>
          <span style={{ fontSize: 12.5, color: T.ink, flex: 1 }}>Reset link sent to {resetMsg}</span>
          <button onClick={() => setResetMsg(null)} style={{ background: "none", border: "none", color: T.muted, cursor: "pointer", padding: 0, display: "flex" }}>
            <Icon name="x-mark" size={14} />
          </button>
        </div>
      )}

      {/* Delete confirm — centered Filament modal */}
      {confirm && (
        <div style={{
          position: "fixed", inset: 0, background: "rgba(15,23,42,0.45)",
          display: "flex", alignItems: "center", justifyContent: "center",
          zIndex: 200, padding: "0 20px",
        }}>
          <div style={{ background: "#fff", borderRadius: 12, padding: "22px", width: "100%", maxWidth: 360 }}>
            <div style={{
              width: 38, height: 38, borderRadius: 9, background: T.dangerSoft, color: T.danger,
              display: "flex", alignItems: "center", justifyContent: "center", marginBottom: 14,
            }}><Icon name="exclamation-triangle" size={19} /></div>
            <p style={{ fontSize: 15, fontWeight: 700, color: T.ink, margin: "0 0 6px" }}>Delete user?</p>
            <p style={{ fontSize: 12.5, color: T.body, margin: "0 0 18px", lineHeight: 1.5 }}>
              This user will be permanently removed. Their timesheet history will remain.
            </p>
            <div style={{ display: "flex", justifyContent: "flex-end", gap: 8 }}>
              <Btn variant="secondary" onClick={() => setConfirm(null)}>Cancel</Btn>
              <Btn variant="danger" onClick={() => setConfirm(null)}>Delete</Btn>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
