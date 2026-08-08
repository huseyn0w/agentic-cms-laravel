import { useState } from 'react';
import { PostCover } from './PostCover';

interface PostImageProps {
  thumbnail: string | null;
  coverSeed: string;
  title?: string;
  alt?: string;
  imgClassName?: string;
  coverClassName?: string;
  width?: number;
  height?: number;
}

/**
 * A post's lead image with graceful degradation. Renders the thumbnail when one
 * exists and loads; otherwise (null thumbnail, or a 404/broken URL that fires
 * onError) it swaps to the deterministic PostCover so a post never shows a
 * broken-image icon or empty box.
 */
export function PostImage({
  thumbnail,
  coverSeed,
  title,
  alt = '',
  imgClassName,
  coverClassName,
  width,
  height,
}: PostImageProps) {
  const [failed, setFailed] = useState(false);

  if (!thumbnail || failed) {
    return <PostCover seed={coverSeed} title={title} className={coverClassName} />;
  }

  return (
    <img
      src={thumbnail}
      alt={alt}
      width={width}
      height={height}
      className={imgClassName}
      onError={() => setFailed(true)}
    />
  );
}
