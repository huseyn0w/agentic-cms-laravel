interface PostCoverProps {
  seed: string;
  title?: string;
  className?: string;
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

/**
 * Original, copyright-safe cover art for posts without a thumbnail. A violet
 * diagonal gradient (theme tokens) plus a few geometric shapes whose positions
 * derive deterministically from the seed (post slug), so a post always gets the
 * same cover. Fully self-contained — no external requests, no licensing.
 */
export function PostCover({ seed, title, className }: PostCoverProps) {
  const h = hash(seed);
  const gid = `pc-${(h % 1000000).toString(36)}`;
  // Derive stable shape params from disjoint bit-slices of the hash.
  const cx1 = 15 + (h % 40);
  const cy1 = 20 + ((h >> 4) % 50);
  const r1 = 12 + ((h >> 8) % 22);
  const cx2 = 55 + ((h >> 12) % 40);
  const cy2 = 40 + ((h >> 16) % 45);
  const r2 = 8 + ((h >> 20) % 18);
  const rot = (h >> 24) % 360;

  return (
    <svg
      viewBox="0 0 100 56"
      preserveAspectRatio="xMidYMid slice"
      role="img"
      aria-label={title ? `${title} cover` : 'Post cover'}
      className={className}
    >
      <defs>
        <linearGradient id={gid} x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stopColor="var(--g1)" />
          <stop offset="45%" stopColor="var(--g2)" />
          <stop offset="100%" stopColor="var(--g3)" />
        </linearGradient>
      </defs>
      <rect width="100" height="56" fill={`url(#${gid})`} />
      <g opacity="0.18" fill="#ffffff" transform={`rotate(${rot} 50 28)`}>
        <circle cx={cx1} cy={cy1} r={r1} />
        <circle cx={cx2} cy={cy2} r={r2} />
        <rect x={cx1} y={cy2} width={r2 * 1.5} height={r2 * 1.5} rx="2" />
      </g>
    </svg>
  );
}
