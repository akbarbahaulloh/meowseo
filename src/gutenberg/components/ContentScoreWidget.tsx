/**
 * ContentScoreWidget Component
 * 
 * Displays SEO and readability scores with color-coded indicators
 * and provides an analyze button to trigger content analysis.
 * 
 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 5.1, 5.2, 5.3, 5.4, 5.5
 */

import { useSelect, useDispatch } from '@wordpress/data';
import { Button, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { STORE_NAME } from '../store';
import './ContentScoreWidget.css';

/**
 * Get color based on score
 * - Red: score < 40
 * - Orange: score 40-69
 * - Green: score >= 70
 */
const getScoreColor = (score: number): string => {
  if (score < 40) {
    return '#dc3232'; // Red
  } else if (score < 70) {
    return '#f56e28'; // Orange
  } else {
    return '#46b450'; // Green
  }
};

export const ContentScoreWidget: React.FC = () => {
  const { seoScore, readabilityScore, isAnalyzing } = useSelect((select) => {
    const store = select(STORE_NAME) as any;
    return {
      seoScore: store.getSeoScore(),
      readabilityScore: store.getReadabilityScore(),
      isAnalyzing: store.getIsAnalyzing(),
    };
  }, []);

  const { analyzeContent } = useDispatch(STORE_NAME) as any;

  const handleAnalyze = () => {
    analyzeContent();
  };

  return (
    <div className="meowseo-content-score-widget">
      <div className="meowseo-scores">
        <div className="meowseo-score-item">
          <div className="meowseo-score-label">
            {__('SEO Score', 'meowseo')}
          </div>
          <div
            className="meowseo-score-value"
            style={{ color: getScoreColor(seoScore) }}
            data-testid="seo-score"
          >
            {seoScore}
          </div>
        </div>
        <div className="meowseo-score-item">
          <div className="meowseo-score-label">
            {__('Readability Score', 'meowseo')}
          </div>
          <div
            className="meowseo-score-value"
            style={{ color: getScoreColor(readabilityScore) }}
            data-testid="readability-score"
          >
            {readabilityScore}
          </div>
        </div>
      </div>
      <div className="meowseo-analyze-button-wrapper">
        <Button
          variant="primary"
          onClick={handleAnalyze}
          disabled={isAnalyzing}
          data-testid="analyze-button"
        >
          {isAnalyzing ? (
            <>
              <Spinner />
              {__('Analyzing...', 'meowseo')}
            </>
          ) : (
            __('Analyze', 'meowseo')
          )}
        </Button>
      </div>
    </div>
  );
};
