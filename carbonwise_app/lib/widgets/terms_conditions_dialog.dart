import 'package:flutter/material.dart';

Future<bool?> showTermsDialog(BuildContext context) {
  bool agreed = false;

  return showDialog<bool>(
    context: context,
    barrierDismissible: false,
    builder: (context) {
      return StatefulBuilder(
        builder: (context, setState) {
          return Dialog(
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(24),
            ),
            insetPadding: const EdgeInsets.symmetric(
              horizontal: 16,
              vertical: 24,
            ),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 460, maxHeight: 620),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  // ============================
                  // HEADER
                  // ============================
                  Container(
                    padding: const EdgeInsets.fromLTRB(22, 22, 22, 18),
                    decoration: const BoxDecoration(
                      color: Color(0xFFEAF6EE),
                      borderRadius: BorderRadius.only(
                        topLeft: Radius.circular(24),
                        topRight: Radius.circular(24),
                      ),
                    ),
                    child: Column(
                      children: [
                        Container(
                          width: 56,
                          height: 56,
                          decoration: const BoxDecoration(
                            color: Colors.white,
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(
                            Icons.description_rounded,
                            color: Color(0xFF3AA76D),
                            size: 28,
                          ),
                        ),
                        const SizedBox(height: 12),
                        const Text(
                          "Terms & Conditions",
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                            color: Color(0xFF1F2933),
                          ),
                        ),
                        const SizedBox(height: 4),
                        const Text(
                          "Please read carefully before continuing.",
                          textAlign: TextAlign.center,
                          style: TextStyle(fontSize: 12, color: Colors.black54),
                        ),
                      ],
                    ),
                  ),

                  // ============================
                  // SCROLLABLE CONTENT
                  // ============================
                  Flexible(
                    child: Container(
                      width: double.infinity,
                      color: Colors.white,
                      child: SingleChildScrollView(
                        padding: const EdgeInsets.fromLTRB(22, 20, 22, 16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: const [
                            _SectionTitle("Welcome to CarbonWise"),
                            SizedBox(height: 8),
                            _BodyText(
                              "By creating an account, you agree to the terms below. "
                              "CarbonWise helps you track your carbon footprint, "
                              "compare with peers, and access personalized insights.",
                            ),

                            SizedBox(height: 20),

                            _SectionTitle("Your Responsibilities"),
                            SizedBox(height: 8),
                            _BulletItem(
                              "Provide accurate personal information.",
                            ),
                            _BulletItem(
                              "Use the platform responsibly and ethically.",
                            ),
                            _BulletItem("Keep your login credentials secure."),

                            SizedBox(height: 20),

                            _SectionTitle("Data We Collect"),
                            SizedBox(height: 8),
                            _BodyText(
                              "CarbonWise collects the following information:",
                            ),
                            SizedBox(height: 8),
                            _BulletItem("Name"),
                            _BulletItem("Student Number / SR-Code"),
                            _BulletItem("G-Suite Email"),
                            _BulletItem("Department & Campus"),
                            _BulletItem("Profile Picture (optional)"),
                            _BulletItem("Carbon Emission Records"),

                            SizedBox(height: 20),

                            _SectionTitle("How Your Data Is Used"),
                            SizedBox(height: 8),
                            _BulletItem("Carbon emission tracking"),
                            _BulletItem("CarbonWise score computation"),
                            _BulletItem("Rankings and comparisons"),
                            _BulletItem("Reports and analytics"),
                            _BulletItem("AI-based forecasting"),
                            _BulletItem("Academic research"),

                            SizedBox(height: 20),

                            _SectionTitle("Data Privacy"),
                            SizedBox(height: 8),
                            _BodyText(
                              "Your personal information will not be shared with "
                              "unauthorized third parties. All data is stored securely "
                              "and handled according to applicable privacy regulations.",
                            ),

                            SizedBox(height: 20),

                            _SectionTitle("AI Forecast Disclaimer"),
                            SizedBox(height: 8),
                            _BodyText(
                              "Carbon forecasts generated by the AI are estimates "
                              "based on historical patterns. They should be used "
                              "as guidance only, not as professional advice.",
                            ),

                            SizedBox(height: 20),

                            _SectionTitle("Acknowledgement"),
                            SizedBox(height: 8),
                            _BodyText(
                              "By continuing, you acknowledge that you have read, "
                              "understood, and accepted these Terms and Conditions.",
                            ),

                            SizedBox(height: 8),
                          ],
                        ),
                      ),
                    ),
                  ),

                  // ============================
                  // FOOTER (checkbox + buttons)
                  // ============================
                  Container(
                    padding: const EdgeInsets.fromLTRB(22, 12, 22, 18),
                    decoration: const BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.only(
                        bottomLeft: Radius.circular(24),
                        bottomRight: Radius.circular(24),
                      ),
                    ),
                    child: Column(
                      children: [
                        // Checkbox row
                        InkWell(
                          onTap: () {
                            setState(() {
                              agreed = !agreed;
                            });
                          },
                          borderRadius: BorderRadius.circular(10),
                          child: Padding(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 4,
                              vertical: 6,
                            ),
                            child: Row(
                              children: [
                                SizedBox(
                                  width: 22,
                                  height: 22,
                                  child: Checkbox(
                                    value: agreed,
                                    activeColor: const Color(0xFF3AA76D),
                                    checkColor: Colors.white,
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(5),
                                    ),
                                    side: BorderSide(
                                      color: agreed
                                          ? const Color(0xFF3AA76D)
                                          : Colors.grey.shade400,
                                      width: 1.5,
                                    ),
                                    onChanged: (value) {
                                      setState(() {
                                        agreed = value ?? false;
                                      });
                                    },
                                  ),
                                ),
                                const SizedBox(width: 10),
                                const Expanded(
                                  child: Text(
                                    "I have read and agree to the Terms & Conditions",
                                    style: TextStyle(
                                      fontSize: 12,
                                      color: Color(0xFF1F2933),
                                      height: 1.3,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),

                        const SizedBox(height: 8),

                        // Buttons
                        Row(
                          children: [
                            Expanded(
                              child: OutlinedButton(
                                onPressed: () {
                                  Navigator.pop(context, false);
                                },
                                style: OutlinedButton.styleFrom(
                                  minimumSize: const Size.fromHeight(46),
                                  side: BorderSide(
                                    color: Colors.grey.shade300,
                                    width: 1.5,
                                  ),
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                                  foregroundColor: Colors.black54,
                                ),
                                child: const Text(
                                  "Cancel",
                                  style: TextStyle(fontWeight: FontWeight.bold),
                                ),
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              flex: 2,
                              child: ElevatedButton(
                                onPressed: agreed
                                    ? () {
                                        Navigator.pop(context, true);
                                      }
                                    : null,
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: const Color(0xFF3AA76D),
                                  foregroundColor: Colors.white,
                                  disabledBackgroundColor: const Color(
                                    0xFF3AA76D,
                                  ).withValues(alpha: 0.35),
                                  disabledForegroundColor: Colors.white
                                      .withValues(alpha: 0.7),
                                  minimumSize: const Size.fromHeight(46),
                                  elevation: 0,
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                                ),
                                child: const Text(
                                  "I Agree",
                                  style: TextStyle(
                                    fontWeight: FontWeight.bold,
                                    fontSize: 14,
                                  ),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      );
    },
  );
}

// ============================
// HELPER WIDGETS
// ============================

class _SectionTitle extends StatelessWidget {
  final String text;
  const _SectionTitle(this.text);

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        Container(
          width: 4,
          height: 16,
          decoration: BoxDecoration(
            color: const Color(0xFF3AA76D),
            borderRadius: BorderRadius.circular(2),
          ),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            text,
            style: const TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.bold,
              color: Color(0xFF1F2933),
            ),
          ),
        ),
      ],
    );
  }
}

class _BodyText extends StatelessWidget {
  final String text;
  const _BodyText(this.text);

  @override
  Widget build(BuildContext context) {
    return Text(
      text,
      style: const TextStyle(
        fontSize: 13,
        height: 1.55,
        color: Color(0xFF4B5563),
      ),
    );
  }
}

class _BulletItem extends StatelessWidget {
  final String text;
  const _BulletItem(this.text);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            margin: const EdgeInsets.only(top: 6),
            width: 6,
            height: 6,
            decoration: const BoxDecoration(
              color: Color(0xFF3AA76D),
              shape: BoxShape.circle,
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              text,
              style: const TextStyle(
                fontSize: 13,
                height: 1.5,
                color: Color(0xFF4B5563),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
