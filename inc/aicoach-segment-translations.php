<?php
/**
 * Translated spoken scripts for the AI Coach's pre-rendered segments (FR-16).
 *
 * Gary's registration/scripts manifest only ever carries reviewed English —
 * confirmed live, GET /coach/v1/health reports registration_languages: ["en"] —
 * so there is no Gary-side source for any other language's spoken content.
 * This is OUR OWN content, sourced from the "Landing page Onboarding process
 * transaltions" Google Sheet linked on the PO-3062 epic (downloaded as .xlsx
 * 2026-09-25 and parsed programmatically — not hand-transcribed from
 * screenshots — to eliminate transcription risk on scripts that get spoken
 * aloud by the coach). Each segment's row range was verified by
 * reconstructing the English column and diffing it against the exact
 * approved string in js/aicoach-coach-flow.js's SCREENS/EQUITY_SCREENS —
 * every segment matched exactly except magic_johnson, where the sheet's own
 * English has a harmless "or the first time…" vs our approved "for the first
 * time…" (a typo in the sheet's source column, not a translation error; every
 * language's actual translation still conveys "for the first time").
 *
 * inc/aicoach-prerender.php renders whatever's here through the same Coach
 * API pipeline as English — confirmed live (2026-09-25): a short Japanese
 * test through this exact pipeline, same avatar/voice, came back sounding
 * right (the reason to build this out at all).
 *
 * FR-16 was confirmed with product (Ivan/Filip, 2026-09-25) as a SINGLE
 * avatar and voice for all seven languages — not a different avatar per
 * language — so there's nothing else per-language to configure here beyond
 * the script text itself.
 *
 * time_selection has no entry: the sheet's "Time Ask" tab turned out to only
 * hold the tier-card UI labels (2/5/10 minutes, step lists — the same
 * strings as I18N_EN's tier-* keys in js/aicoach-coach-flow.js), not the
 * spoken "Now it's your turn…" narration. That translation doesn't exist yet
 * anywhere — not a bug here, a real content gap to flag upstream.
 *
 * A segment with no entry for a given language (or an entry that's just an
 * empty string) simply doesn't get rendered in that language yet —
 * ihq_aicoach_prerender_get_urls() falls back to the English clip, same
 * "missing just means not ready" degrade path as everywhere else in this
 * feature.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return array<string,array<string,string>> segment_key => language => text.
 */
function ihq_aicoach_segment_translations() {
	return array(
		'we_believe_1' => array(
			'zh' => '每一家成功的企业，都始于一套核心信念。 以下就是我们的其中之一。 我们相信，影响力的价值远远不只是创作内容， 而是创造持久的价值。 大多数网红获得的回报，都仅限于他们今天所做的事情。 但我们相信，那些协助打造未来的网红，也应该有所回报，有机会分享他们亲手创造的成果。 这就是为什么“实质股权”是网红总部的核心所在。 让我为你详细说明。',
			'yue' => '每一家成功的企業，都始於一套核心信念。 以下就是我們的其中之一。 我們相信，影響力的價值遠遠不只是創作內容， 而是創造持久的價值。 大多數網紅獲得的回報，都僅限於他們今天所做的事情。 但我們相信，那些協助打造未來的網紅，也應該要有所回報，有機會分享他們親手創造的成果。 這就是為什麼「實質股權」是網紅總部的核心所在。 讓我為你詳細說明。',
			'ja' => '成功している企業には、必ずその土台となる考え方があります。 そのひとつをご紹介します。 インフルエンサーの影響力というのは、単にコンテンツを作ることだけではないと考えています。 それは、長く続く価値を生み出すことではないでしょうか。 多くのインフルエンサーは、依頼された仕事を完了することで報酬を受け取ります。 ですが、これからのビジネス構築に貢献するインフルエンサーには、生み出した価値から恩恵を受ける機会もあるべきです。 だからこそ、相応の持ち株の機会を提供することが、インフルエンサーHQの考え方の中心にあります。 どういうことか、ご説明します。',
			'th' => 'บริษัทที่ประสบความสำเร็จทุกแห่งล้วนเริ่มต้นจากความเชื่อบางอย่าง และนี่คือหนึ่งในความเชื่อของเรา เราเชื่อว่าการเป็นอินฟลูเอนเซอร์มีอะไรมากกว่าแค่ทำคอนเทนต์ แต่ยังเป็นการสร้างคุณค่าในระยะยาวด้วย อินฟลูเอนเซอร์ส่วนใหญ่ได้ค่าตอบแทนจากงานที่ทำในวันนี้ เราเชื่อว่าอินฟลูเอนเซอร์ที่ช่วยสร้างอนาคตของบริษัท ก็ควรมีโอกาสได้รับผลตอบแทนจากสิ่งที่ตัวเองช่วยสร้างขึ้นมาด้วย โอกาสได้ถือหุ้นในสัดส่วนที่คุ้มกับความทุ่มเท จึงเป็นหัวใจของศูนย์กลางใหญ่อินฟลูเอนเซอร์ เดี๋ยวจะอธิบายให้เห็นภาพ',
			'vi' => 'Mỗi công ty thành công đều bắt đầu từ những niềm tin nhất định. Đây là một trong những niềm tin của chúng tôi. Chúng tôi tin rằng sức ảnh hưởng không chỉ nằm ở việc tạo ra nội dung. Nó còn nằm ở việc tạo ra giá trị lâu dài. Phần lớn influencer được trả công cho những gì họ làm hôm nay. Chúng tôi tin rằng những influencer góp phần xây dựng tương lai cũng nên có cơ hội hưởng thành quả từ những gì họ góp sức tạo ra. Đó là lý do InfluencerHQ đặt cơ hội sở hữu cổ phần làm trọng tâm. Để tôi giải thích rõ hơn.',
			'ko' => '모든 성공적인 기업은 일련의 신념에서 시작됩니다. 이것은 저희의 신념 중 하나입니다. 우리는 인플루언서의 영향력이 단순히 콘텐츠를 제작하는 것 그 이상이라고 믿습니다. 그것은 지속 가능한 가치를 창출하는 것에 관한 것입니다. 대부분의 인플루언서는 오늘 한 일에 대해 보상을 받습니다. 우리는 내일을 함께 만들어 가는 인플루언서들이 자신이 창출하는데 기여한 가치로부터 혜택을 얻을 기회도 가져야 한다고 믿습니다. 이것이 바로 의미 있는 지분이 인플루언서HQ의 핵심인 이유입니다. 무슨 뜻인지 설명해 드리겠습니다.',
		),
		'we_believe_2' => array(
			'zh' => '还有另一个同样重要的信念： 我们相信，只要是协助创造价值的人，就应该有机会共享这份成果。 不是遥远的未来…… 而是从一开始就是如此。 这与传统网红获得报酬的方式截然不同。 当你拥有实质的股权时，它的潜在价值往往远超过一次性的单笔酬劳。 这不只是我们的观点而已。 让我为你举几个真实的例子。',
			'yue' => '還有另一個同樣重要的信念： 我們相信，只要是協助創造價值的人，就應該有機會共享這份成果。 不是遙遠的未來…… 而是從一開始就是如此。 這與傳統網紅獲得報酬的方式截然不同。 當你擁有實質的股權時，它的潛在價值往往遠超過一次性的單筆酬勞。 這不只是我們的觀點而已。 讓我為你舉幾個真實的例子。',
			'ja' => 'もうひとつ、同様に大切な考え方があります。 価値を生み出すことに貢献した人には、それを分かち合う機会があるべきだと考えています。 その機会はいつかではなく 最初からです。 これは、多くのインフルエンサーが従来受け取ってきた報酬のあり方とは大きく異なります。 相応の持ち株を持つ機会があれば、それは一度きりの報酬よりも、はるかに大きな価値になる可能性があります。 これは、単なる私たちの意見ではありません。 実際の事例をいくつかご紹介します。',
			'th' => 'ยังมีอีกเรื่องที่เราเชื่อและให้ความสำคัญไม่แพ้กัน เราเชื่อว่าคนที่ช่วยสร้างมูลค่า ก็ควรมีโอกาสได้รับส่วนแบ่งจากมูลค่าที่ตัวเองช่วยสร้างขึ้นด้วย ไม่ใช่กว่าจะได้ต้องรอไปไม่รู้จบ… แต่เราตั้งแต่เริ่มต้นเลย ต่างจากรูปแบบค่าตอบแทนเดิมๆ ที่ปกติอินฟลูเอนเซอร์ส่วนใหญ่จะได้กัน เมื่อคุณมีโอกาสได้ถือหุ้นในสัดส่วนที่คุ้มค่า หุ้นนั้นอาจมีมูลค่าเพิ่มขึ้นจนสูงกว่าค่าตอบแทนที่รับแค่ครั้งเดียวไปมาก นี่ไม่ได้พูดลอยๆ มาดูตัวอย่างจริงกันสักสองสามเรื่อง',
			'vi' => 'Chúng tôi còn có một niềm tin quan trọng không kém. Chúng tôi tin rằng những người góp phần tạo ra giá trị cũng nên có cơ hội hưởng lợi từ giá trị đó. Không phải chờ đến một ngày nào đó… Mà là ngay từ đầu. Điều đó khác hẳn với cách phần lớn influencer vẫn được trả công. Khi có cơ hội sở hữu một phần công ty thì phần sở hữu ấy có thể đáng giá hơn nhiều so với khoản tiền công chỉ nhận một lần. Đây không chỉ là ý kiến của chúng tôi. Để tôi cho bạn thấy một vài ví dụ thực tế.',
			'ko' => '마찬가지로 중요한 또 다른 신념이 있습니다. 우리는 가치 창출을 돕는 사람들이 그 가치를 함께 나눌 기회를 가져야 한다고 믿습니다. 언젠가가 아니라... 바로 처음부터 말이죠. 이는 대부분의 인플루언서들이 보상받는 전통적인 방식과는 매우 다릅니다. 의미 있는 지분을 가질 수 있게 되면 일회성 지급액보다 훨씬 더 큰 가치를 지닐 수 있습니다. 이것은 단지 저희만의 의견이 아닙니다. 몇 가지 실제 사례들을 보여드리겠습니다.',
		),
		'time_selection' => array(), // see top-of-file note — not in the sheet yet.
		'magic_johnson'  => array(
			'zh' => '篮球巨星魔术强森曾获得历史上最伟大的股权持有机会之一。 然而…… 他选择接受了传统的代言方案。 据估计，这个决定让他错失了价值约 54 亿美元的股权收益。 没有人能预测未来。 并不是每一个股权机会都能成功。 但是当正确的股权机会出现时…… 它的价值可能会远远超过眼前的现金报酬。 今天…… 历史上第一次…… 网红们也开始获得类似的股权机会。 让我们一起来看看其中一个例子。',
			'yue' => '籃球巨星魔術強森曾獲得歷史上最偉大的股權持有機會之一。 然而…… 他選擇接受了傳統的代言方案。 據估計，這個決定讓他錯失了價值約 54 億美元的股權收益。 沒有人能預測未來。 並不是每一個股權機會都能成功。 但是當正確的股權機會出現時…… 它的價值可能會遠遠超過眼前的現金報酬。 今天…… 歷史上第一次…… 網紅們也開始獲得類似的股權機會。 讓我們一起來看看其中一個例子。',
			'ja' => 'バスケットボール界のスター、マジック・ジョンソンには、歴史に残るほど大きな持ち株の機会が提示されていました。 ところが 彼が選んだのは、従来型のスポンサー契約でした。 その選択によって、持ち株として得られた可能性のある価値、およそ54億ドルを逃したとも言われています。 もちろん、未来を予測することは誰にもできません。 すべての持ち株の機会が成功するわけでもありません。 ですが、もし適切な持ち株の機会に巡り合えることができれば 目先の即金をはるかに上回る価値になる可能性があります。 そして今、 初めて インフルエンサーにも、同様の持ち株のチャンスが生まれ始めています。 そのひとつを見てみましょう。',
			'th' => 'เมจิก จอห์นสัน นักบาสเกตบอลชื่อดัง เคยได้รับข้อเสนอให้ถือหุ้นที่นับเป็นหนึ่งในโอกาสครั้งใหญ่ที่สุดในประวัติศาสตร์ แต่เขากลับ… เลือกรับค่าจ้างเป็นพรีเซนเตอร์แบบที่ทำกันทั่วไป ว่ากันว่าการตัดสินใจครั้งนั้น ทำให้เขาพลาดโอกาสเป็นเจ้าของหุ้นมูลค่าราว 5.4 พันล้านดอลลาร์ ไม่มีใครรู้ล่วงหน้าว่าอนาคตจะเป็นยังไง และไม่ใช่ว่าได้ถือหุ้นแล้วจะประสบความสำเร็จเสมอไป แต่ถ้าเจอโอกาสถือหุ้นที่ใช่… หุ้นนั้นก็อาจมีมูลค่าในอนาคตสูงกว่าเงินสดที่รับทันทีไปมาก วันนี้… เป็นครั้งแรกที่… อินฟลูเอนเซอร์เริ่มได้รับโอกาสถือหุ้นแบบเดียวกันนี้บ้างแล้ว มาดูกันสักตัวอย่าง',
			'vi' => 'Ngôi sao bóng rổ Magic Johnson từng được đề nghị một trong những cơ hội sở hữu cổ phần lớn nhất lịch sử. Nhưng thay vào đó… ông đã nhận một hợp đồng quảng cáo theo cách truyền thống. Ước tính quyết định ấy đã khiến ông bỏ lỡ khoảng 5,4 tỷ USD giá trị cổ phần. Không ai có thể đoán trước tương lai. Không phải cơ hội sở hữu cổ phần nào cũng thành công. Nhưng khi có được cơ hội phù hợp… số cổ phần đó có thể đáng giá hơn nhiều so với số tiền mặt được trả ngay lúc đó. Ngày nay… lần đầu tiên… các influencer đang bắt đầu có những cơ hội sở hữu cổ phần tương tự. Hãy cùng xem một ví dụ.',
			'ko' => '농구 스타 매직 존슨은 역사상 가장 위대한 지분 참여 기회 중 하나를 받았습니다. 하지만 그 대신... 그는 전통적인 광고 계약을 받아들였습니다. 그 결정으로 인해 그는 약 54억 달러 상당의 지분 가치를  날린 것으로 추정됩니다. 아무도 미래를 예측할 수 없습니다. 모든 지분 참여의 기회가 성공하는 것도 아닙니다. 하지만 적절한 지분 기회가 찾아왔을 때... 당장의 현금보다 훨씬 큰 가치를 지니게 될 수 있습니다. 오늘날... 처음으로... 인플루언서들이 이와 유사한 지분 참여 기회를 얻기 시작하고 있습니다. 그중 하나를 살펴보겠습니다.',
		),
		'alix_earle' => array(
			'zh' => '网红艾莉克斯·厄尔做出了一个截然不同的决定。 她没有仅仅接受传统的现金赞助…… 而是与 Poppi 谈判，争取到了股权合作的机会。 不到三年后…… 百事可乐以近 20 亿美元的价格收购了 Poppi。 她的故事提醒着我们，股权机会不再局限于运动员、娱乐明星或商业领袖。 今天…… 网红也有机会超越眼前的现金报酬…… 并参与到他们亲手协助创造的长期价值之中。 现在……让我们来看一个国际上的例子。',
			'yue' => '網紅艾莉克斯·厄爾做出了一個截然不同的決定。 她沒有僅僅接受傳統的現金贊助…… 而是與 Poppi 談判，爭取到了股權合作的機會。 不到三年後…… 百事可樂以近 20 億美元的價格收購了 Poppi。 她的故事提醒著我們，股權機會不再局限於運動員、娛樂明星或商業領袖。 今天…… 網紅也有機會超越眼前的現金報酬…… 並參與到他們親手協助創造的長期價值之中。 現在……讓我們來看一個國際上的例子。',
			'ja' => 'インフルエンサーのアリックス・アールは、異なる選択をしました。 従来の現金によるスポンサー契約だけを結ぶのではなく、 Poppiとの間で、持ち株を得られる契約を交渉しました。 それから3年も経たないうちに、 ペプシがPoppiを約20億ドルで買収しました。 彼女の事例は、持ち株報酬の機会がもはやアスリートや芸能人、ビジネスリーダーだけのものではないことを示しています。 今日では、 インフルエンサーも目先の現金報酬だけにとらわれず、 自らが生み出す長期的なリターンに参加できる機会を持てるようになっています。 では次に、 他の事例を見てみましょう。',
			'th' => 'อินฟลูเอนเซอร์อย่างแอลิกซ์ เอิร์ล ตัดสินใจเลือกอีกทาง แทนที่จะรับแค่เงินสปอนเซอร์แบบที่ทำกันทั่วไป… เธอเจรจากับ Poppi เพื่อให้มีโอกาสได้ถือหุ้นในบริษัทด้วย ผ่านไปไม่ถึง 3 ปี… PepsiCo ก็เข้าซื้อกิจการ Poppi ด้วยมูลค่าเกือบ 2 พันล้านดอลลาร์ เรื่องของเธอทำให้เห็นว่า โอกาสได้ถือหุ้นไม่ได้จำกัดอยู่แค่ในกลุ่มนักกีฬา คนในวงการบันเทิง หรือผู้บริหารธุรกิจอีกต่อไป วันนี้… อินฟลูเอนเซอร์ก็มีโอกาสมองไกลกว่าเงินสดที่ได้ทันที… และมีส่วนรับผลตอบแทนระยะยาวจากสิ่งที่ตัวเองช่วยสร้างขึ้นมา ทีนี้… ลองมาดูตัวอย่างจากต่างประเทศกันบ้าง',
			'vi' => 'Influencer Alix Earle đã đưa ra một lựa chọn khác. Thay vì chỉ nhận tiền từ một hợp đồng tài trợ theo cách truyền thống… cô đã thương lượng để có cơ hội sở hữu cổ phần của Poppi. Chưa đầy ba năm sau… PepsiCo mua lại Poppi với giá gần 2 tỷ USD. Câu chuyện của cô cho thấy cơ hội sở hữu cổ phần không còn chỉ dành cho các vận động viên, người nổi tiếng hay lãnh đạo doanh nghiệp. Ngày nay… các influencer cũng có cơ hội nhìn xa hơn số tiền họ được trả ngay lúc đó… và hưởng một phần giá trị lâu dài mà họ góp sức tạo ra. Bây giờ… hãy cùng xem một ví dụ ở một quốc gia khác.',
			'ko' => '인플루언서 알릭스 얼은 다른 결정을 내렸습니다. 전통적인 현금 후원 방식만 수락하는 대신... 그녀는 포피(Poppi.)와 지분 참여 기회를 협상했습니다. 3년도 채 되지 않아... 펩시코(PepsiCo)가 포피( Poppi)를 약 20억 달러에 인수했습니다. 그녀의 이야기는 지분 참여의 기회가 더 이상 운동선수, 연예인,기업 리더에게만 국한되지 않는다는 점을 일깨워 줍니다. 오늘날... 인플루언서들은 당장의 현금 그 이상을 생각하고... 자신이 창출하는 데 기여한 장기적인 가치에 참여하는 기회를 갖고 있습니다. 이제 국제적인 사례를 살펴보겠습니다.',
		),
		'bts' => array(
			'zh' => '国际音乐团体 BTS 也同样看到了持有的力量。 他们没有仅仅依赖传统的酬劳方式…… 而是参与到了自己亲手协助打造的长期价值之中。 他们的股权价值达到了数亿美元。 这给我们的启示无关篮球…… 无关社交媒体…… 也无关音乐。 关键在于当正确的股权机会出现时，能够及时把握。 这正是成立网红总部的原因。 现在…… 让我为你展示只要短短几分钟，你能达成什么。',
			'yue' => '國際音樂團體 BTS 也同樣看到了持有的力量。 他們沒有僅僅依賴傳統的酬勞方式…… 而是參與到了自己親手協助打造的長期價值之中。 他們的股權價值達到了數億美元。 這給我們的啟示無關籃球…… 無關社群媒體…… 也無關音樂。 關鍵在於當正確的股權機會出現時，能夠及時把握。 這正是成立網紅總部的原因。 現在…… 讓我為你展示只要短短幾分鐘，你能達成什麼。',
			'ja' => '世界的な音楽グループBTSも、持ち株を得ることのパワーを認識していました。 従来の報酬だけを受け取るのではなく、 みずからが構築したものが生み出す、長期的な価値への参加権も得ました。 その持ち株は、数億ドル規模の価値になりました。 ここで大切なのは、バスケットボールでも、 ソーシャルメディアでも、 音楽でもありません。 重要なのは、適切な持ち株の機会が訪れたときに、それを見極めることができるかです。 まさにそのために、インフルエンサーHQは誕生しました。 それでは 実際に何が達成できるのか、ほんの数分でご紹介します。',
			'th' => 'วงดนตรีระดับโลกอย่าง BTS ก็เห็นความสำคัญของการมีส่วนเป็นเจ้าของธุรกิจเช่นกัน แทนที่จะพึ่งแค่ค่าตอบแทนแบบเดิมๆ… พวกเขายังได้ประโยชน์จากมูลค่าในระยะยาวของสิ่งที่ตัวเองช่วยสร้างขึ้นมาด้วย หุ้นที่พวกเขาถืออยู่มีมูลค่าเพิ่มขึ้นเป็นหลายร้อยล้านดอลลาร์ ประเด็นของเรื่องนี้ไม่ได้อยู่ที่บาสเกตบอล… หรือโซเชียลมีเดีย… หรือวงการเพลง แต่อยู่ที่การมองเห็นโอกาสร่วมเป็นเจ้าของธุรกิจที่ใช่ เมื่อโอกาสนั้นมาถึง นี่แหละคือเหตุผลที่เราสร้างศูนย์กลางใหญ่อินฟลูเอนเซอร์ขึ้นมา ทีนี้… มาดูกันว่าในเวลาแค่ไม่กี่นาที คุณทำอะไรสำเร็จได้บ้าง',
			'vi' => 'Nhóm nhạc quốc tế BTS cũng nhận ra giá trị của việc sở hữu cổ phần. Thay vì chỉ nhận thù lao theo cách truyền thống… họ còn được hưởng giá trị lâu dài từ những gì mình đã góp phần xây dựng. Số cổ phần họ sở hữu sau đó có giá trị lên tới hàng trăm triệu USD. Bài học ở đây không nằm ở bóng rổ… hay mạng xã hội… hay âm nhạc. Mà là biết nhận ra cơ hội sở hữu cổ phần phù hợp khi nó xuất hiện. Đó chính là lý do InfluencerHQ ra đời. Bây giờ… để tôi cho bạn thấy bạn có thể làm được gì chỉ trong vài phút.',
			'ko' => '세계적인 음악 그룹 BTS 역시 지분의 힘을 인식했습니다. 전통적인 보상 방식에만 의존하는 대신... 그들은 자신들이 구축하는 데 기여한 장기적 가치 창출에 참여했습니다. 그들의 지분 가치는 수억 달러에 이르게 되었습니다. 이 교훈은 농구에 관한 것이 아닙니다... 소셜 미디어에 관한 것도 아닙니다... 또는 음악에 관한 것도 아니죠. 적절한 지분 참여 기회가 왔을 때 이를 알아보는 것에 관한 이야기입니다. 이것이 바로 인플루언서HQ가 탄생한 이유입니다. 이제... 단 몇 분 만에 여러분이 무엇을 성취할 수 있는지 보여드리겠습니다',
		),
	);
}
