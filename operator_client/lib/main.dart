import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'dart:async';
import 'package:window_manager/window_manager.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Custom error reporting for Windows
  ErrorWidget.builder = (FlutterErrorDetails details) {
    return Material(
      child: Container(
        padding: const EdgeInsets.all(20),
        color: Colors.red.shade900,
        child: SingleChildScrollView(
          child: Text(
            "CRITICAL ERROR:\n\n${details.exception}\n\n${details.stack}",
            style: const TextStyle(
              color: Colors.white,
              fontSize: 10,
              fontFamily: 'monospace',
            ),
          ),
        ),
      ),
    );
  };

  await windowManager.ensureInitialized();

  // Load saved settings
  final prefs = await SharedPreferences.getInstance();
  final savedOpacity = prefs.getDouble('opacity') ?? 1.0;
  final savedAlwaysOnTop = prefs.getBool('always_on_top') ?? true;

  WindowOptions windowOptions = const WindowOptions(
    size: Size(350, 480),
    center: true,
    backgroundColor: Colors.white,
    skipTaskbar: false,
    titleBarStyle: TitleBarStyle.normal,
    title: "Panel Operator",
  );

  windowManager.waitUntilReadyToShow(windowOptions, () async {
    await windowManager.show();
    await windowManager.focus();
    await windowManager.setAlwaysOnTop(savedAlwaysOnTop);
    await windowManager.setOpacity(savedOpacity);
  });

  runApp(const OperatorApp());
}

class OperatorApp extends StatelessWidget {
  const OperatorApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        useMaterial3: true,
        colorSchemeSeed: Colors.blue,
        brightness: Brightness.light,
      ),
      home: const OperatorPanel(),
    );
  }
}

class OperatorPanel extends StatefulWidget {
  const OperatorPanel({super.key});

  @override
  State<OperatorPanel> createState() => _OperatorPanelState();
}

class _OperatorPanelState extends State<OperatorPanel> {
  String serverIp = "";
  int counterId = 1;
  double opacity = 1.0;
  bool alwaysOnTop = true;

  Map<String, dynamic>? statusData;
  String? errorMessage;
  bool isLoading = false;
  Timer? refreshTimer;
  bool isConfiguring = true;

  late TextEditingController _ipController;
  late TextEditingController _counterController;

  @override
  void initState() {
    super.initState();
    _ipController = TextEditingController();
    _counterController = TextEditingController(text: "1");

    loadSettings().then((_) {
      if (serverIp.isNotEmpty && serverIp != "localhost:8000") {
        fetchStatus();
      }
    });

    refreshTimer = Timer.periodic(
      const Duration(seconds: 4),
      (_) => fetchStatus(),
    );
  }

  Future<void> loadSettings() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      serverIp = prefs.getString('server_ip') ?? "";
      counterId = prefs.getInt('counter_id') ?? 1;
      opacity = prefs.getDouble('opacity') ?? 1.0;
      alwaysOnTop = prefs.getBool('always_on_top') ?? true;
      _ipController.text = serverIp;
      _counterController.text = counterId.toString();
      if (serverIp.isNotEmpty) {
        isConfiguring = false;
      }
    });
    // Apply window settings
    await windowManager.setOpacity(opacity);
    await windowManager.setAlwaysOnTop(alwaysOnTop);
  }

  Future<void> saveSettings() async {
    final newIp = _ipController.text.trim();
    if (newIp.isEmpty) {
      setState(() => errorMessage = "IP Server tidak boleh kosong");
      return;
    }

    setState(() {
      isLoading = true;
      errorMessage = null;
    });

    try {
      final testBaseUrl = "http://$newIp/api/operator";
      final testCounterId = int.tryParse(_counterController.text) ?? 1;

      final response = await http
          .get(Uri.parse('$testBaseUrl/status/$testCounterId'))
          .timeout(const Duration(seconds: 5));

      if (response.statusCode == 200) {
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('server_ip', newIp);
        await prefs.setInt('counter_id', testCounterId);

        setState(() {
          serverIp = newIp;
          counterId = testCounterId;
          statusData = json.decode(response.body)['data'];
          isConfiguring = false;
          errorMessage = null;
        });
      } else {
        setState(() {
          errorMessage = "Server error (${response.statusCode}). Cek IP/Loket.";
        });
      }
    } catch (e) {
      setState(() {
        errorMessage =
            "Koneksi Gagal.\nPastikan server aktif di $newIp\nError: $e";
      });
    } finally {
      setState(() => isLoading = false);
    }
  }

  @override
  void dispose() {
    refreshTimer?.cancel();
    _ipController.dispose();
    _counterController.dispose();
    super.dispose();
  }

  String get baseUrl => "http://$serverIp/api/operator";

  Future<void> fetchStatus() async {
    if (isConfiguring || serverIp.isEmpty) return;
    try {
      final response = await http
          .get(Uri.parse('$baseUrl/status/$counterId'))
          .timeout(const Duration(seconds: 4));

      if (response.statusCode == 200) {
        final decoded = json.decode(response.body);
        debugPrint("RAW RESPONSE: ${response.body}");
        debugPrint("DECODED DATA: ${decoded['data']}");
        debugPrint("STATS IN DATA: ${decoded['data']?['stats']}");
        debugPrint(
          "CURRENT_QUEUE: ${decoded['data']?['stats']?['current_queue']}",
        );
        setState(() {
          statusData = decoded['data'];
          errorMessage = null;
        });
      } else {
        setState(() => errorMessage = "Server Status: ${response.statusCode}");
      }
    } catch (e) {
      debugPrint("Update error: $e");
    }
  }

  Future<void> callNext() async {
    setState(() => isLoading = true);
    try {
      final response = await http.post(Uri.parse('$baseUrl/call/$counterId'));
      if (response.statusCode == 200) {
        fetchStatus(); // Immediately update view
      } else {
        final body = json.decode(response.body);
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(body['message'] ?? "Gagal memanggil.")),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text("Error: $e")));
      }
    } finally {
      setState(() => isLoading = false);
    }
  }

  int? _getQueueId() {
    final stats = statusData?['stats'];
    final rawQueue = stats?['current_queue'];
    if (rawQueue == null) return null;
    return rawQueue['id'];
  }

  Future<void> recall() async {
    final queueId = _getQueueId();
    if (queueId == null) return;
    try {
      await http.post(Uri.parse('$baseUrl/queue/$queueId/recall'));
    } catch (e) {
      debugPrint("Recall error: $e");
    }
  }

  Future<void> complete() async {
    final queueId = _getQueueId();
    if (queueId == null) return;
    try {
      await http.post(Uri.parse('$baseUrl/queue/$queueId/served'));
      fetchStatus();
    } catch (e) {
      debugPrint("Complete error: $e");
    }
  }

  Future<void> skip() async {
    final queueId = _getQueueId();
    if (queueId == null) return;
    try {
      await http.post(Uri.parse('$baseUrl/queue/$queueId/skip'));
      fetchStatus();
    } catch (e) {
      debugPrint("Skip error: $e");
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: Material(
        color: Colors.white,
        child: isConfiguring ? buildConfigView() : buildMainView(),
      ),
    );
  }

  Widget buildMainView() {
    if (statusData == null && errorMessage != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.error_outline, color: Colors.red, size: 48),
              const SizedBox(height: 16),
              Text(
                errorMessage!,
                textAlign: TextAlign.center,
                style: const TextStyle(color: Colors.red),
              ),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: () => setState(() => isConfiguring = true),
                child: const Text("Buka Pengaturan"),
              ),
            ],
          ),
        ),
      );
    }

    if (statusData == null) {
      return const Center(child: CircularProgressIndicator());
    }

    final stats = statusData?['stats'];
    final rawQueue = stats?['current_queue'];
    final Map<String, dynamic>? currentQueue = rawQueue != null
        ? Map<String, dynamic>.from(rawQueue)
        : null;

    debugPrint("STATS: $stats");
    debugPrint("RAW QUEUE: $rawQueue");
    debugPrint("PARSED QUEUE: $currentQueue");

    final waitingCount = stats?['waiting_count'] ?? 0;
    final bool enablePhoto =
        (stats?['enable_photo_capture'] == true ||
        stats?['enable_photo_capture'] == 1);
    final String? photoUrl = currentQueue?['photo_url']?.toString();
    final String number = currentQueue?['full_number']?.toString() ?? "---";
    final String serviceName = (currentQueue?['service']?['name'] ?? "ANTRIAN")
        .toString()
        .toUpperCase();

    return Padding(
      padding: const EdgeInsets.all(16.0),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    "LOKET $counterId",
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      color: Colors.blue,
                    ),
                  ),
                  Text(
                    serverIp,
                    style: const TextStyle(fontSize: 10, color: Colors.grey),
                  ),
                ],
              ),
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: Colors.blue.shade50,
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      "ANTRI: $waitingCount",
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        color: Colors.blue,
                        fontSize: 12,
                      ),
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.settings, size: 18),
                    onPressed: () => setState(() => isConfiguring = true),
                  ),
                ],
              ),
            ],
          ),
          const Divider(),
          Expanded(
            child: Center(
              child: currentQueue != null
                  ? Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        if (enablePhoto &&
                            photoUrl != null &&
                            photoUrl.isNotEmpty)
                          Padding(
                            padding: const EdgeInsets.only(right: 12),
                            child: ClipRRect(
                              borderRadius: BorderRadius.circular(8),
                              child: Image.network(
                                photoUrl.startsWith('http')
                                    ? photoUrl
                                    : "http://$serverIp$photoUrl",
                                width: 80,
                                height: 80,
                                fit: BoxFit.cover,
                                errorBuilder: (context, error, stackTrace) =>
                                    _emptyProfile(),
                              ),
                            ),
                          ),
                        Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              number,
                              style: const TextStyle(
                                fontSize: 48,
                                fontWeight: FontWeight.w900,
                                height: 1,
                              ),
                            ),
                            Text(
                              serviceName,
                              style: const TextStyle(
                                fontSize: 12,
                                color: Colors.blue,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ],
                        ),
                      ],
                    )
                  : const Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          Icons.inbox_outlined,
                          size: 48,
                          color: Colors.grey,
                        ),
                        Text(
                          "TIDAK ADA ANTRIAN",
                          style: TextStyle(
                            color: Colors.grey,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
            ),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: ElevatedButton(
                  onPressed: (isLoading || waitingCount == 0) ? null : callNext,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: waitingCount > 0
                        ? Colors.blue
                        : Colors.grey.shade300,
                    foregroundColor: Colors.white,
                    disabledBackgroundColor: Colors.grey.shade300,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                  ),
                  child: isLoading
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            color: Colors.white,
                            strokeWidth: 2,
                          ),
                        )
                      : Text(waitingCount > 0 ? "PANGGIL" : "ANTRIAN KOSONG"),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          // Action buttons like web: Panggil Ulang, Selesai, Lewati
          Row(
            children: [
              Expanded(
                child: _labeledButton(
                  onPressed: currentQueue != null ? recall : null,
                  icon: Icons.record_voice_over,
                  label: "PANGGIL ULANG",
                  color: Colors.orange,
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _labeledButton(
                  onPressed: currentQueue != null ? complete : null,
                  icon: Icons.check_circle,
                  label: "SELESAI",
                  color: Colors.green,
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _labeledButton(
                  onPressed: currentQueue != null ? skip : null,
                  icon: Icons.skip_next,
                  label: "LEWATI",
                  color: Colors.red,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _labeledButton({
    required VoidCallback? onPressed,
    required IconData icon,
    required String label,
    required Color color,
  }) {
    return ElevatedButton(
      onPressed: onPressed,
      style: ElevatedButton.styleFrom(
        backgroundColor: color,
        foregroundColor: Colors.white,
        disabledBackgroundColor: Colors.grey.shade300,
        padding: const EdgeInsets.symmetric(vertical: 10),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 20),
          const SizedBox(height: 2),
          Text(
            label,
            style: const TextStyle(fontSize: 9, fontWeight: FontWeight.bold),
          ),
        ],
      ),
    );
  }

  Widget _emptyProfile() {
    return Container(
      width: 80,
      height: 80,
      color: Colors.grey.shade100,
      child: const Icon(Icons.person, color: Colors.grey),
    );
  }

  Widget buildConfigView() {
    return SingleChildScrollView(
      child: Padding(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text(
              "PENGATURAN",
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Colors.blue,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              "Hubungkan ke server untuk mulai memanggil antrian.",
              style: TextStyle(fontSize: 12, color: Colors.grey),
            ),
            const SizedBox(height: 24),
            if (errorMessage != null)
              Container(
                margin: const EdgeInsets.only(bottom: 16),
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.red.shade50,
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Colors.red.shade200),
                ),
                child: Text(
                  errorMessage!,
                  style: const TextStyle(color: Colors.red, fontSize: 12),
                ),
              ),
            TextField(
              controller: _ipController,
              decoration: const InputDecoration(
                labelText: "IP Server",
                hintText: "127.0.0.1:8000",
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: _counterController,
              decoration: const InputDecoration(
                labelText: "Nomor Loket",
                border: OutlineInputBorder(),
              ),
              keyboardType: TextInputType.number,
            ),
            const SizedBox(height: 16),
            // Always on top toggle
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  "Selalu di Atas",
                  style: TextStyle(fontWeight: FontWeight.w500),
                ),
                Switch(
                  value: alwaysOnTop,
                  onChanged: (value) async {
                    setState(() => alwaysOnTop = value);
                    await windowManager.setAlwaysOnTop(value);
                    final prefs = await SharedPreferences.getInstance();
                    await prefs.setBool('always_on_top', value);
                  },
                ),
              ],
            ),
            // Opacity slider
            Row(
              children: [
                const Text(
                  "Transparansi",
                  style: TextStyle(fontWeight: FontWeight.w500),
                ),
                Expanded(
                  child: Slider(
                    value: opacity,
                    min: 0.3,
                    max: 1.0,
                    divisions: 7,
                    label: "${(opacity * 100).round()}%",
                    onChanged: (value) async {
                      setState(() => opacity = value);
                      await windowManager.setOpacity(value);
                    },
                    onChangeEnd: (value) async {
                      final prefs = await SharedPreferences.getInstance();
                      await prefs.setDouble('opacity', value);
                    },
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              height: 50,
              child: ElevatedButton(
                onPressed: isLoading ? null : saveSettings,
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.blue,
                  foregroundColor: Colors.white,
                ),
                child: isLoading
                    ? const CircularProgressIndicator(color: Colors.white)
                    : const Text("SIMPAN & HUBUNGKAN"),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
