import { useState } from "react";

const T = {
  primary: "#1B3860", primaryDk: "#102648",
  ink: "#0F172A", body: "#475569", muted: "#94A3B8",
  line: "#E2E8F0", bg: "#F1F5F9", card: "#FFFFFF",
  accent: "#8B1520",
};

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

function Icon({ name, size = 17 }) {
  const paths = {
    eye: <><path d="M2 12s3.5-6.5 10-6.5S22 12 22 12s-3.5 6.5-10 6.5S2 12 2 12z" /><circle cx="12" cy="12" r="2.6" /></>,
    "eye-off": <><path d="M2 12s3.5-6.5 10-6.5S22 12 22 12s-3.5 6.5-10 6.5S2 12 2 12z" /><circle cx="12" cy="12" r="2.6" /><line x1="3.5" y1="3.5" x2="20.5" y2="20.5" /></>,
    check: <polyline points="5 12.5 9.5 17 19 6" />,
  };
  return (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor"
      strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" style={{ display: "block" }}>
      {paths[name]}
    </svg>
  );
}

export default function Login() {
  const [email, setEmail] = useState("");
  const [pw, setPw] = useState("");
  const [showPw, setShowPw] = useState(false);
  const [remember, setRemember] = useState(false);
  const [loading, setLoading] = useState(false);
  const [focused, setFocused] = useState(null);

  const submit = () => { setLoading(true); setTimeout(() => setLoading(false), 1500); };

  const fieldStyle = (name, hasValue) => ({
    width: "100%", boxSizing: "border-box",
    padding: "10px 12px", fontSize: 14, color: T.ink,
    background: focused === name ? "#fff" : "#F8FAFC",
    border: `1.5px solid ${focused === name ? T.primary : T.line}`,
    borderRadius: 8, outline: "none", fontFamily: "inherit",
    transition: "border-color 0.15s, background 0.15s",
  });

  return (
    <div style={{
      fontFamily: "'Inter',system-ui,sans-serif",
      minHeight: "100vh", maxWidth: 420, margin: "0 auto",
      background: T.bg, display: "flex", flexDirection: "column",
      justifyContent: "center", padding: "32px 18px",
    }}>

      {/* Brand row */}
      <div style={{ display: "flex", flexDirection: "column", alignItems: "center", gap: 8, marginBottom: 26 }}>
        <QuatrizLogo height={34} />
        <p style={{ color: T.muted, fontSize: 11, letterSpacing: "0.1em", textTransform: "uppercase", margin: 0, fontWeight: 600 }}>
          Timesheet System
        </p>
      </div>

      {/* Card */}
      <div style={{
        background: T.card, border: `1px solid ${T.line}`, borderRadius: 12,
        padding: "28px 24px", boxShadow: "0 1px 3px rgba(15,23,42,0.04)",
      }}>
        <h2 style={{ fontSize: 19, fontWeight: 700, color: T.ink, margin: "0 0 4px" }}>Sign in</h2>
        <p style={{ fontSize: 13, color: T.muted, margin: "0 0 24px" }}>
          Enter your Quatriz credentials to continue
        </p>

        {/* Email */}
        <div style={{ marginBottom: 16 }}>
          <label style={{ fontSize: 12.5, fontWeight: 600, color: T.body, display: "block", marginBottom: 6 }}>
            Email address
          </label>
          <input
            type="email" value={email}
            onChange={e => setEmail(e.target.value)}
            onFocus={() => setFocused("email")}
            onBlur={() => setFocused(null)}
            placeholder="you@quatriz.com.my"
            style={fieldStyle("email")}
          />
        </div>

        {/* Password */}
        <div style={{ marginBottom: 16 }}>
          <label style={{ fontSize: 12.5, fontWeight: 600, color: T.body, display: "block", marginBottom: 6 }}>
            Password
          </label>
          <div style={{ position: "relative" }}>
            <input
              type={showPw ? "text" : "password"} value={pw}
              onChange={e => setPw(e.target.value)}
              onFocus={() => setFocused("pw")}
              onBlur={() => setFocused(null)}
              placeholder="••••••••"
              style={{ ...fieldStyle("pw"), paddingRight: 38 }}
            />
            <button
              onClick={() => setShowPw(!showPw)}
              style={{
                position: "absolute", right: 10, top: "50%", transform: "translateY(-50%)",
                background: "none", border: "none", cursor: "pointer", color: T.muted, padding: 0, display: "flex",
              }}
            ><Icon name={showPw ? "eye-off" : "eye"} size={16} /></button>
          </div>
        </div>

        {/* Remember + forgot */}
        <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", marginBottom: 22 }}>
          <label style={{ display: "flex", alignItems: "center", gap: 8, cursor: "pointer" }}>
            <div
              onClick={() => setRemember(!remember)}
              style={{
                width: 16, height: 16, borderRadius: 4,
                border: `1.5px solid ${remember ? T.primary : T.line}`,
                background: remember ? T.primary : "#fff",
                display: "flex", alignItems: "center", justifyContent: "center",
                flexShrink: 0, transition: "all 0.15s",
              }}
            >
              {remember && <Icon name="check" size={10} />}
            </div>
            <span style={{ fontSize: 13, color: T.body, userSelect: "none" }}>Remember me</span>
          </label>
          <button style={{ background: "none", border: "none", cursor: "pointer", fontSize: 12.5, color: T.primary, padding: 0, fontWeight: 600 }}>
            Forgot password?
          </button>
        </div>

        {/* Sign in button */}
        <button
          onClick={submit}
          style={{
            width: "100%", padding: "11px",
            background: loading ? T.muted : T.primary,
            border: "none", borderRadius: 8,
            color: "#fff", fontSize: 13.5, fontWeight: 600,
            cursor: loading ? "not-allowed" : "pointer",
            fontFamily: "inherit",
          }}
        >
          {loading ? "Signing in…" : "Sign in"}
        </button>
      </div>

      <p style={{ fontSize: 11.5, color: T.muted, textAlign: "center", marginTop: 22, marginBottom: 0 }}>
        © 2026 Quatriz System Sdn Bhd
      </p>
    </div>
  );
}
