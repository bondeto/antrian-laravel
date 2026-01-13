export function useQueueVoice() {
    const playAudio = (path) => {
        return new Promise((resolve, reject) => {
            const audio = new Audio(path);
            audio.onended = resolve;
            audio.onerror = reject;
            audio.play().catch(reject);
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
        try {
            const basePath = '/bandara';
            const audioList = [];

            // 1. Nomor Antrean
            audioList.push(`${basePath}/frasa/nomor-antrean.wav`);

            // 2. Huruf (Layanan)
            const prefix = queue.full_number.split('-')[0];
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
            // Try to detect if it's "Loket" or "Meja" from name or just use "Loket"
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
        } catch (error) {
            console.error('Error playing queue voice:', error);
            // Fallback to browser TTS if audio not found?
        }
    };

    return { playQueueCall };
}
