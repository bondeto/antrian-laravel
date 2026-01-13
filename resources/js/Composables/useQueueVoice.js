export function useQueueVoice() {
    const playAudio = (path) => {
        console.log(`[QueueVoice] Playing: ${path}`);
        return new Promise((resolve, reject) => {
            const audio = new Audio(path);
            audio.onended = () => {
                console.log(`[QueueVoice] Finished: ${path}`);
                resolve();
            };
            audio.onerror = (e) => {
                console.error(`[QueueVoice] Error playing ${path}:`, e);
                reject(e);
            };
            audio.play().catch(err => {
                console.warn(`[QueueVoice] Autoplay blocked or failed for ${path}. Please interact with the page first.`, err);
                reject(err);
            });
        });
    };

    const getNumberSequence = (num) => {
        const sequence = [];
        if (num === 0) return ['0'];

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

    const playQueueCall = async (queue) => {
        console.log('[QueueVoice] Starting call sequence for:', queue.full_number);
        try {
            const basePath = window.location.origin + '/bandara';
            const audioList = [];

            // 1. Nomor Antrean
            audioList.push(`${basePath}/frasa/nomor-antrean.wav`);

            // 2. Huruf (Layanan)
            const prefix = queue.full_number.split('-')[0].toUpperCase();
            audioList.push(`${basePath}/huruf/${prefix}.wav`);

            // 3. Angka Nomor
            const num = parseInt(queue.number);
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
            const counterName = queue.counter?.name || '';
            const counterMatch = counterName.match(/\d+/);
            if (counterMatch) {
                const counterNum = parseInt(counterMatch[0]);
                const counterSeq = getNumberSequence(counterNum);
                counterSeq.forEach(s => {
                    if (['seratus', 'seribu', 'ratus', 'ribu', 'puluh', 'belas'].includes(s)) {
                        audioList.push(`${basePath}/satuan/${s}.wav`);
                    } else {
                        audioList.push(`${basePath}/angka/${s}.wav`);
                    }
                });
            }

            // Play one by one
            for (const audioPath of audioList) {
                await playAudio(audioPath);
            }
            console.log('[QueueVoice] Sequence completed.');
        } catch (error) {
            console.error('[QueueVoice] Critical Error in sequence:', error);
        }
    };

    return { playQueueCall };
}
