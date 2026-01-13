// Queue to manage multiple calls sequentially
const callQueue = [];
let isProcessing = false;

export function useQueueVoice() {
    const playAudio = (path) => {
        return new Promise((resolve, reject) => {
            const audio = new Audio(path);
            audio.onended = () => resolve();
            audio.onerror = (e) => {
                console.warn(`[QueueVoice] Failed to load audio: ${path}`);
                resolve(); // Continue even if one file fails
            };
            audio.play().catch(err => {
                console.warn(`[QueueVoice] Failed to play audio: ${path}`, err);
                resolve(); // Continue even if play fails
            });
        });
    };

    const getNumberSequence = (num) => {
        const sequence = [];
        if (num === 0) return ['0'];
        if (isNaN(num)) return ['0'];

        if (num === 100) return ['seratus'];
        if (num === 1000) return ['seribu'];

        const hundreds = Math.floor(num / 100);
        const remainder100 = num % 100;

        if (hundreds > 0) {
            if (hundreds === 1) {
                sequence.push('seratus');
            } else {
                sequence.push(hundreds.toString());
                sequence.push('ratus');
            }
        }

        if (remainder100 > 0) {
            if (remainder100 < 12) {
                if (remainder100 === 10) sequence.push('10');
                else if (remainder100 === 11) sequence.push('11');
                else sequence.push(remainder100.toString());
            } else if (remainder100 < 20) {
                sequence.push((remainder100 % 10).toString());
                sequence.push('belas');
            } else {
                const tens = Math.floor(remainder100 / 10);
                const ones = remainder100 % 10;
                sequence.push(tens.toString());
                sequence.push('puluh');
                if (ones > 0) sequence.push(ones.toString());
            }
        }

        return sequence;
    };

    const processQueue = async () => {
        if (isProcessing || callQueue.length === 0) return;

        isProcessing = true;
        const queueData = callQueue.shift();

        console.log('[QueueVoice] Processing queue data:', queueData);

        if (!queueData || !queueData.full_number) {
            console.error('[QueueVoice] Invalid queue data - missing full_number');
            isProcessing = false;
            processQueue();
            return;
        }

        try {
            const basePath = window.location.origin + '/bandara';
            const audioList = [];

            // 1. Nomor Antrean
            audioList.push(`${basePath}/frasa/nomor-antrean.wav`);

            // 2. Huruf (Layanan) - extract from full_number like "A-001"
            const parts = queueData.full_number.split('-');
            const prefix = parts[0]?.toUpperCase() || 'A';
            audioList.push(`${basePath}/huruf/${prefix}.wav`);

            // 3. Angka Nomor - use number field OR extract from full_number
            let num = queueData.number;
            if (num === undefined || num === null) {
                // Fallback: extract from full_number (e.g., "A-001" -> 1)
                const numPart = parts[1] || '0';
                num = parseInt(numPart, 10);
            } else {
                num = parseInt(num, 10);
            }

            console.log('[QueueVoice] Extracted number:', num);

            const numSeq = getNumberSequence(num);
            numSeq.forEach(s => {
                if (['seratus', 'seribu', 'ratus', 'ribu', 'puluh', 'belas'].includes(s)) {
                    audioList.push(`${basePath}/satuan/${s}.wav`);
                } else {
                    audioList.push(`${basePath}/angka/${s}.wav`);
                }
            });

            // 4. Silakan Menuju
            audioList.push(`${basePath}/frasa/silakan-menuju.wav`);

            // 5. Loket / Meja
            audioList.push(`${basePath}/frasa/loket.wav`);

            // 6. Nomor Loket
            const counterName = queueData.counter?.name || '';
            const counterMatch = counterName.match(/\d+/);
            if (counterMatch) {
                const counterNum = parseInt(counterMatch[0], 10);
                const counterSeq = getNumberSequence(counterNum);
                counterSeq.forEach(s => {
                    if (['seratus', 'seribu', 'ratus', 'ribu', 'puluh', 'belas'].includes(s)) {
                        audioList.push(`${basePath}/satuan/${s}.wav`);
                    } else {
                        audioList.push(`${basePath}/angka/${s}.wav`);
                    }
                });
            }

            console.log('[QueueVoice] Audio sequence:', audioList);

            // Play one by one
            for (const audioPath of audioList) {
                await playAudio(audioPath);
            }
            console.log('[QueueVoice] Sequence completed for:', queueData.full_number);

            // Pause slightly between different calls
            await new Promise(resolve => setTimeout(resolve, 1500));

        } catch (error) {
            console.error('[QueueVoice] Error playing sequence:', error);
        } finally {
            isProcessing = false;
            // Move to next in queue
            processQueue();
        }
    };

    const playQueueCall = (queue) => {
        console.log('[QueueVoice] Adding to queue:', queue?.full_number);
        callQueue.push(queue);
        processQueue();
    };

    return { playQueueCall };
}
