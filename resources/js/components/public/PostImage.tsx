import { useState } from 'react';
import { preload } from 'react-dom';
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
  /**
   * The post's lead image (above the fold): fetched eagerly with high priority
   * so it appears with the text instead of after it. Leave false for gallery /
   * related-post thumbnails so they stay lazy and off the critical path.
   */
  priority?: boolean;
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
  priority = false,
}: PostImageProps) {
  const [failed, setFailed] = useState(false);

  // For the lead image, start the download during render (before <img> mounts)
  // so it arrives with the text on an Inertia client visit, not after it.
  if (priority && thumbnail && !failed) {
    preload(thumbnail, { as: 'image', fetchPriority: 'high' });
  }

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
      loading={priority ? 'eager' : 'lazy'}
      fetchPriority={priority ? 'high' : 'auto'}
      decoding="async"
      onError={() => setFailed(true)}
    />
  );
}
