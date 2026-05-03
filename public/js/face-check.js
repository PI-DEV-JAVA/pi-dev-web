/**
 * Talentos AI Face Check — Browser-based portrait validation
 * Uses face-api.js (TensorFlow.js) for real-time face detection
 * No API keys needed — runs 100% client-side
 */

const FACE_CHECK = {
    loaded: false,
    loading: false,
    MODEL_URL: '/models/face-api',

    async loadModels() {
        if (this.loaded || this.loading) return this.loaded;
        this.loading = true;
        try {
            await faceapi.nets.tinyFaceDetector.loadFromUri(this.MODEL_URL);
            await faceapi.nets.faceExpressionNet.loadFromUri(this.MODEL_URL);
            this.loaded = true;
        } catch (e) {
            console.warn('Face-API models failed to load:', e);
            this.loaded = false;
        }
        this.loading = false;
        return this.loaded;
    },

    /**
     * Analyze an image element for face detection
     * @param {HTMLImageElement|HTMLCanvasElement} imgEl
     * @returns {Object} { hasFace, faceCount, confidence, expression, tips, boxRatio }
     */
    async analyze(imgEl) {
        const result = {
            hasFace: false,
            faceCount: 0,
            confidence: 0,
            expression: null,
            tips: [],
            status: 'analyzing'
        };

        if (!this.loaded) {
            const ok = await this.loadModels();
            if (!ok) {
                result.status = 'error';
                result.tips.push({ icon: 'exclamation-triangle', text: 'Analyse IA indisponible', type: 'warn' });
                return result;
            }
        }

        try {
            const detections = await faceapi
                .detectAllFaces(imgEl, new faceapi.TinyFaceDetectorOptions({ inputSize: 416, scoreThreshold: 0.4 }))
                .withFaceExpressions();

            result.faceCount = detections.length;

            if (detections.length === 0) {
                result.hasFace = false;
                result.status = 'no_face';
                result.tips.push({ icon: 'person-x-fill', text: 'Aucun visage humain détecté', type: 'error' });
                result.tips.push({ icon: 'lightbulb', text: 'Utilisez une vraie photo de vous, bien éclairée', type: 'info' });
                return result;
            }

            if (detections.length > 1) {
                result.hasFace = true;
                result.status = 'multiple';
                result.confidence = Math.round(detections[0].detection.score * 100);
                result.tips.push({ icon: 'people-fill', text: `${detections.length} visages détectés — utilisez une photo individuelle`, type: 'warn' });
                return result;
            }

            // Single face detected
            const det = detections[0];
            result.hasFace = true;
            result.status = 'ok';
            result.confidence = Math.round(det.detection.score * 100);

            // Check face size relative to image
            const box = det.detection.box;
            const imgArea = imgEl.naturalWidth * imgEl.naturalHeight || imgEl.width * imgEl.height;
            const faceArea = box.width * box.height;
            const faceRatio = faceArea / imgArea;

            if (faceRatio < 0.03) {
                result.tips.push({ icon: 'zoom-in', text: 'Votre visage est trop petit — rapprochez-vous', type: 'warn' });
            } else if (faceRatio > 0.5) {
                result.tips.push({ icon: 'arrows-angle-contract', text: 'Cadrage très serré — un peu plus de recul serait idéal', type: 'info' });
            }

            // Expression analysis
            if (det.expressions) {
                const exprEntries = Object.entries(det.expressions);
                exprEntries.sort((a, b) => b[1] - a[1]);
                const topExpr = exprEntries[0];
                result.expression = topExpr[0];

                const exprMap = {
                    happy: { icon: 'emoji-smile', text: 'Beau sourire ! Parfait pour votre profil 😊', type: 'success' },
                    neutral: { icon: 'emoji-neutral', text: 'Expression neutre — professionnelle et sobre ✓', type: 'success' },
                    surprised: { icon: 'emoji-surprise', text: 'Vous avez l\'air surpris — essayez un sourire naturel', type: 'info' },
                    sad: { icon: 'emoji-frown', text: 'Essayez un sourire léger pour un look plus accueillant', type: 'info' },
                    angry: { icon: 'emoji-angry', text: 'Un sourire rendrait votre profil plus chaleureux', type: 'info' },
                    fearful: { icon: 'emoji-dizzy', text: 'Détendez-vous et essayez un sourire naturel', type: 'info' },
                    disgusted: { icon: 'emoji-expressionless', text: 'Essayez une expression plus accueillante', type: 'info' },
                };
                if (exprMap[topExpr[0]]) {
                    result.tips.push(exprMap[topExpr[0]]);
                }
            }

            // Quality tips
            if (result.confidence >= 90) {
                result.tips.unshift({ icon: 'star-fill', text: `Excellent portrait (${result.confidence}% confiance)`, type: 'success' });
            } else if (result.confidence >= 70) {
                result.tips.unshift({ icon: 'check-circle-fill', text: `Bon portrait (${result.confidence}% confiance)`, type: 'success' });
            } else {
                result.tips.unshift({ icon: 'exclamation-circle', text: `Portrait acceptable (${result.confidence}% confiance) — améliorez l'éclairage`, type: 'warn' });
            }

        } catch (e) {
            console.error('Face check error:', e);
            result.status = 'error';
            result.tips.push({ icon: 'exclamation-triangle', text: 'Erreur lors de l\'analyse', type: 'warn' });
        }

        return result;
    },

    /**
     * Render analysis results into a container element
     * @param {HTMLElement} container
     * @param {Object} result from analyze()
     */
    renderResults(container, result) {
        if (!container) return;
        let html = '';

        // Confidence bar
        if (result.hasFace && result.confidence > 0) {
            const color = result.confidence >= 80 ? '#10b981' : result.confidence >= 60 ? '#f59e0b' : '#ef4444';
            html += `<div style="margin-bottom:12px">
                <div style="display:flex;justify-content:space-between;font-size:0.72rem;font-weight:600;margin-bottom:4px">
                    <span>Qualité du portrait</span>
                    <span style="color:${color}">${result.confidence}%</span>
                </div>
                <div style="height:6px;background:rgba(255,255,255,0.1);border-radius:3px;overflow:hidden">
                    <div style="height:100%;width:${result.confidence}%;background:${color};border-radius:3px;transition:width 0.8s cubic-bezier(0.16,1,0.3,1)"></div>
                </div>
            </div>`;
        }

        // Tips
        result.tips.forEach(t => {
            const colors = { success: '#10b981', warn: '#f59e0b', error: '#ef4444', info: '#6366f1' };
            const bgColors = { success: 'rgba(16,185,129,0.08)', warn: 'rgba(245,158,11,0.08)', error: 'rgba(239,68,68,0.08)', info: 'rgba(99,102,241,0.08)' };
            html += `<div style="display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;margin-bottom:4px;font-size:0.78rem;background:${bgColors[t.type] || bgColors.info};color:${colors[t.type] || colors.info}">
                <i class="bi bi-${t.icon}" style="font-size:0.9rem;flex-shrink:0"></i>
                <span>${t.text}</span>
            </div>`;
        });

        container.innerHTML = html;
        container.style.display = 'block';
    }
};
