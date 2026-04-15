/**
 * Property-Based Tests for Analysis Worker
 * 
 * Tests universal properties that should hold for all inputs.
 */

import * as fc from 'fast-check';
import { analyzeSEO } from '../analysis-worker';

describe('Analysis Worker - Property Tests', () => {
  /**
   * Property 3: Score bounds
   * Validates: Requirements 7.8, 7.9
   * 
   * Test that for any analysis input, score is always 0 <= score <= 100
   */
  describe('Property 3: Score bounds', () => {
    it('should always return a score between 0 and 100 for any input', () => {
      fc.assert(
        fc.property(
          fc.string(),  // title
          fc.string(),  // description
          fc.string(),  // content
          fc.string(),  // slug
          fc.string(),  // focusKeyword
          (title, description, content, slug, focusKeyword) => {
            const result = analyzeSEO({
              title,
              description,
              content,
              slug,
              focusKeyword,
            });
            
            // Score must be between 0 and 100 (inclusive)
            expect(result.score).toBeGreaterThanOrEqual(0);
            expect(result.score).toBeLessThanOrEqual(100);
          }
        ),
        { numRuns: 1000 }
      );
    });

    it('should return score as a multiple of 20 (0, 20, 40, 60, 80, or 100)', () => {
      fc.assert(
        fc.property(
          fc.string(),
          fc.string(),
          fc.string(),
          fc.string(),
          fc.string(),
          (title, description, content, slug, focusKeyword) => {
            const result = analyzeSEO({
              title,
              description,
              content,
              slug,
              focusKeyword,
            });
            
            // Score should be a multiple of 20 (each check is worth 20 points)
            expect(result.score % 20).toBe(0);
          }
        ),
        { numRuns: 1000 }
      );
    });

    it('should return 0 score when focus keyword is empty', () => {
      fc.assert(
        fc.property(
          fc.string(),
          fc.string(),
          fc.string(),
          fc.string(),
          (title, description, content, slug) => {
            const result = analyzeSEO({
              title,
              description,
              content,
              slug,
              focusKeyword: '',
            });
            
            expect(result.score).toBe(0);
          }
        ),
        { numRuns: 100 }
      );
    });
  });

  /**
   * Property 5: Score color mapping
   * Validates: Requirements 4.3, 4.4, 4.5
   * 
   * Test that color mapping is deterministic based on score thresholds
   */
  describe('Property 5: Score color mapping', () => {
    it('should map scores < 40 to red', () => {
      fc.assert(
        fc.property(
          fc.string(),
          fc.string(),
          fc.string(),
          fc.string(),
          fc.string(),
          (title, description, content, slug, focusKeyword) => {
            const result = analyzeSEO({
              title,
              description,
              content,
              slug,
              focusKeyword,
            });
            
            if (result.score < 40) {
              expect(result.color).toBe('red');
            }
          }
        ),
        { numRuns: 1000 }
      );
    });

    it('should map scores 40-69 to orange', () => {
      fc.assert(
        fc.property(
          fc.string(),
          fc.string(),
          fc.string(),
          fc.string(),
          fc.string(),
          (title, description, content, slug, focusKeyword) => {
            const result = analyzeSEO({
              title,
              description,
              content,
              slug,
              focusKeyword,
            });
            
            if (result.score >= 40 && result.score < 70) {
              expect(result.color).toBe('orange');
            }
          }
        ),
        { numRuns: 1000 }
      );
    });

    it('should map scores >= 70 to green', () => {
      fc.assert(
        fc.property(
          fc.string(),
          fc.string(),
          fc.string(),
          fc.string(),
          fc.string(),
          (title, description, content, slug, focusKeyword) => {
            const result = analyzeSEO({
              title,
              description,
              content,
              slug,
              focusKeyword,
            });
            
            if (result.score >= 70) {
              expect(result.color).toBe('green');
            }
          }
        ),
        { numRuns: 1000 }
      );
    });

    it('should have deterministic color mapping for same score', () => {
      // Test specific score values
      const testScores = [0, 20, 40, 60, 80, 100];
      const expectedColors: Array<'red' | 'orange' | 'green'> = ['red', 'red', 'orange', 'orange', 'green', 'green'];
      
      testScores.forEach((score, index) => {
        // Generate input that produces the target score
        const checks = score / 20;
        const keyword = 'test';
        
        const input = {
          title: checks >= 1 ? keyword : '',
          description: checks >= 2 ? keyword : '',
          content: checks >= 3 ? `<p>${keyword}</p>` : '<p>content</p>',
          slug: checks >= 5 ? keyword : 'slug',
          focusKeyword: keyword,
        };
        
        // Add heading if needed for check 4
        if (checks >= 4) {
          input.content = `<h1>${keyword}</h1>` + input.content;
        }
        
        const result = analyzeSEO(input);
        expect(result.color).toBe(expectedColors[index]);
      });
    });
  });

  /**
   * Property 4: Idempotent analysis
   * Validates: Requirements 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7
   * 
   * Test that running analysis twice on same content produces identical results
   */
  describe('Property 4: Idempotent analysis', () => {
    it('should produce identical results when run twice on the same input', () => {
      fc.assert(
        fc.property(
          fc.string(),
          fc.string(),
          fc.string(),
          fc.string(),
          fc.string(),
          (title, description, content, slug, focusKeyword) => {
            const input = { title, description, content, slug, focusKeyword };
            
            const result1 = analyzeSEO(input);
            const result2 = analyzeSEO(input);
            
            // Scores should be identical
            expect(result1.score).toBe(result2.score);
            
            // Colors should be identical
            expect(result1.color).toBe(result2.color);
            
            // Results array should have same length
            expect(result1.results.length).toBe(result2.results.length);
            
            // Each result should be identical
            result1.results.forEach((r1, index) => {
              const r2 = result2.results[index];
              expect(r1.id).toBe(r2.id);
              expect(r1.type).toBe(r2.type);
              expect(r1.message).toBe(r2.message);
            });
          }
        ),
        { numRuns: 1000 }
      );
    });

    it('should produce consistent results regardless of execution order', () => {
      fc.assert(
        fc.property(
          fc.array(
            fc.record({
              title: fc.string(),
              description: fc.string(),
              content: fc.string(),
              slug: fc.string(),
              focusKeyword: fc.string(),
            }),
            { minLength: 2, maxLength: 5 }
          ),
          (inputs) => {
            // Run analysis on all inputs
            const results1 = inputs.map(input => analyzeSEO(input));
            
            // Run analysis again in reverse order
            const results2 = inputs.reverse().map(input => analyzeSEO(input)).reverse();
            
            // Results should be identical regardless of order
            results1.forEach((r1, index) => {
              const r2 = results2[index];
              expect(r1.score).toBe(r2.score);
              expect(r1.color).toBe(r2.color);
              expect(r1.results.length).toBe(r2.results.length);
            });
          }
        ),
        { numRuns: 100 }
      );
    });
  });
});
