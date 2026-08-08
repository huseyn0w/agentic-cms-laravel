import { useState } from 'react';

/** Two-letter initials from a display name (first + last), or "?" when empty. */
function initials(name: string): string {
  const parts = name.trim().split(/\s+/).filter(Boolean);
  if (parts.length === 0) return '?';
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

/** Small deterministic string hash (FNV-1a-ish) → 32-bit unsigned int. */
function hash(seed: string): number {
  let h = 2166136261;
  for (let i = 0; i < seed.length; i++) {
    h ^= seed.charCodeAt(i);
    h = Math.imul(h, 16777619);
  }
  return h >>> 0;
}

interface AvatarProps {
  src: string | null;
  name: string;
  className?: string;
}

/**
 * A person's avatar with graceful degradation. Renders the photo when one exists
 * and loads; otherwise (null src, or a 404/broken URL that fires onError) it
 * draws initials on a deterministic violet gradient — the same visual language
 * as PostCover — so an avatar never shows a broken-image icon.
 */
export function Avatar({ src, name, className }: AvatarProps) {
  const [failed, setFailed] = useState(false);

  if (!src || failed) {
    const gid = `av-${(hash(name) % 1000000).toString(36)}`;
    return (
      <svg
        viewBox="0 0 100 100"
        preserveAspectRatio="xMidYMid slice"
        role="img"
        aria-label={name || 'Avatar'}
        className={className}
      >
        <defs>
          <linearGradient id={gid} x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stopColor="var(--g1)" />
            <stop offset="50%" stopColor="var(--g2)" />
            <stop offset="100%" stopColor="var(--g3)" />
          </linearGradient>
        </defs>
        <rect width="100" height="100" fill={`url(#${gid})`} />
        <text
          x="50"
          y="50"
          dy="0.35em"
          textAnchor="middle"
          fontSize="42"
          fontWeight="600"
          fill="#ffffff"
          fillOpacity="0.92"
          fontFamily="system-ui, sans-serif"
        >
          {initials(name)}
        </text>
      </svg>
    );
  }

  return <img src={src} alt={name} className={className} onError={() => setFailed(true)} />;
}
