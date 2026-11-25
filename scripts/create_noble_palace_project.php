<?php
/**
 * TẠO MỚI DỰ ÁN NOBLE PALACE LONG BIEN (PILLAR CẤP 2) & CÁC BÀI VIẾT VỆ TINH (CLUSTER CẤP 3)
 */
require_once __DIR__ . '/../wp-load.php';
require_once __DIR__ . '/sync_to_live.php';

$sync = new SunshineLiveSync();

echo "=== 1. TẠO TRANG DỰ ÁN CỘT TRỤ: NOBLE PALACE LONG BIEN ===\n";

$pillar_slug = 'noble-palace-long-bien';
$pillar_title = 'Dự Án Noble Palace Long Biên – Biệt Thự &amp; Shophouse Hoàng Gia Sunshine Group';
$pillar_url = 'https://sunshine-group.com.vn/du-an/' . $pillar_slug . '/';

$content_pillar = <<<HTML
<div class="kbds-project-subnav">
    <div class="kbds-container kbds-subnav-container">
        <div class="kbds-subnav-brand"><span class="kbds-subnav-dot"></span>NOBLE PALACE LONG BIEN</div>
        <nav class="kbds-subnav-links">
            <a href="#tong-quan" class="kbds-subnav-link">Tổng Quan</a>
            <a href="#vi-tri" class="kbds-subnav-link">Vị Trí &amp; Kết Nối</a>
            <a href="#tien-ich" class="kbds-subnav-link">Tiện Ích Hoàng Gia</a>
            <a href="#san-pham" class="kbds-subnav-link">Sản Phẩm</a>
            <a href="#mat-bang" class="kbds-subnav-link">Mặt Bằng</a>
            <a href="#chinh-sach" class="kbds-subnav-link">Chính Sách</a>
            <a href="#hoi-dap" class="kbds-subnav-link">Hỏi Đáp</a>
        </nav>
        <a href="#nhan-bang-gia" class="kbds-subnav-cta">Nhận Báo Giá Gốc</a>
    </div>
</div>

<div class="kbds-container" style="max-width: 1200px; margin: 30px auto; padding: 0 15px;">

    <!-- HERO INTRO -->
    <div style="background: linear-gradient(135deg, #0b2246 0%, #173868 100%); border-radius: 16px; padding: 35px 30px; color: #ffffff; margin-bottom: 35px; box-shadow: 0 10px 30px rgba(11,34,70,0.15);">
        <span style="display:inline-block; background:rgba(212,175,55,0.2); color:#fcfc73; border:1px solid #d4af37; padding:5px 14px; border-radius:20px; font-size:12.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:15px;">Dinh Thự Hoàng Gia • Đất Rồng Phía Đông Thủ Đô</span>
        <h1 style="color:#ffffff; font-size:32px; font-weight:800; line-height:1.3; margin-bottom:16px;">Dự Án Noble Palace Long Biên – Đỉnh Cao Dinh Thự Quý Tộc Bên Bờ Sông Hồng</h1>
        <p style="font-size:16px; line-height:1.7; color:#e2e8f0; margin-bottom:0;">
            Kiêu hãnh hiện diện trên <strong>“Đất rồng Long Biên”</strong> – nơi hai dòng sông huyết mạch sông Hồng và sông Đuống giao hòa, <strong>Noble Palace Long Bien</strong> sở hữu một tọa độ chiến lược hiếm có: chỉ chưa đầy 20 phút kết nối tới sân bay Quốc tế Nội Bài, nằm trong tam giác kinh tế trọng điểm Hà Nội – Hải Phòng – Quảng Ninh, và thụ hưởng toàn bộ lợi thế hạ tầng hiện đại nhất phía Đông Thủ đô.
        </p>
    </div>

    <!-- HỘP ĐĂNG KÝ NHANH THẤP TẦNG -->
    [kbds_quick_lead_card_thap_tang title="ĐĂNG KÝ NHẬN BẢNG GIÁ &amp; QUỸ CĂN NGOẠI GIAO 2026" subtitle="Nhận trọn bộ bảng giá phân lô, hồ sơ pháp lý 1/500 và thư mời tham quan trực tiếp" project="Noble Palace Long Bien"]

    <!-- SECTION 1: TỔNG QUAN -->
    <section id="tong-quan" style="margin-top: 50px;">
        <h2 class="section-title-centered" style="font-size:26px; font-weight:800; color:#0b2246; text-transform:uppercase;">Tổng Quan Dự Án Noble Palace Long Bien</h2>
        <p style="font-size:15.5px; line-height:1.8; color:#334155; text-align:justify;">
            <strong>Noble Palace Long Bien</strong> là kiệt tác bất động sản thấp tầng cao cấp được phát triển bởi Tập đoàn Sunshine dưới thương hiệu bất động sản siêu sang <strong>NOBLE</strong>. Mang phong cách kiến trúc Pháp cổ điển kết hợp Baroque xa hoa, dự án kiến tạo chuẩn mực sống vương giả dành riêng cho giới tinh hoa, nguyên thủ và các chủ nhân danh giá bậc nhất.
        </p>

        <div style="margin: 25px 0; text-align: center;">
            <img src="https://sunshine-group.com.vn/wp-content/uploads/2026/11/phoi-canh-dinh-thu-noble-palace-long-bien.jpg" alt="Phối cảnh dinh thự dự án Noble Palace Long Bien" style="width:100%; max-width:1100px; border-radius:12px; box-shadow:0 8px 24px rgba(0,0,0,0.1);" />
            <p style="font-size:13.5px; color:#64748b; font-style:italic; margin-top:8px;">Phối cảnh kiến trúc dinh thự hoàng gia đẳng cấp tại Noble Palace Long Bien</p>
        </div>

        <!-- BẢNG THÔNG SỐ DỰ ÁN -->
        <table style="width:100%; border-collapse:collapse; margin:25px 0; background:#ffffff; box-shadow:0 4px 15px rgba(0,0,0,0.05); border-radius:8px; overflow:hidden;">
            <tbody>
                <tr style="background:#0b2246; color:#ffffff;">
                    <th colspan="2" style="padding:14px 18px; font-size:16px; text-transform:uppercase; letter-spacing:0.5px;">Bảng Thông Số Tổng Quan Dự Án</th>
                </tr>
                <tr style="border-bottom:1px solid #e2e8f0;">
                    <td style="padding:12px 18px; font-weight:700; color:#0b2246; width:35%; background:#f8fafc;">Tên thương mại</td>
                    <td style="padding:12px 18px; color:#334155;"><strong>Noble Palace Long Bien</strong> (Noble Palace Long Biên)</td>
                </tr>
                <tr style="border-bottom:1px solid #e2e8f0;">
                    <td style="padding:12px 18px; font-weight:700; color:#0b2246; background:#f8fafc;">Chủ đầu tư / Phát triển</td>
                    <td style="padding:12px 18px; color:#334155;">Tập đoàn Sunshine (Thương hiệu BĐS Hạng Sang NOBLE)</td>
                </tr>
                <tr style="border-bottom:1px solid #e2e8f0;">
                    <td style="padding:12px 18px; font-weight:700; color:#0b2246; background:#f8fafc;">Vị trí dự án</td>
                    <td style="padding:12px 18px; color:#334155;">Quận Long Biên, TP. Hà Nội (Tam giác kinh tế Hà Nội – Hải Phòng – Quảng Ninh)</td>
                </tr>
                <tr style="border-bottom:1px solid #e2e8f0;">
                    <td style="padding:12px 18px; font-weight:700; color:#0b2246; background:#f8fafc;">Loại hình phát triển</td>
                    <td style="padding:12px 18px; color:#334155;">Dinh thự độc bản, Biệt thự đơn lập, Biệt thự song lập, Shophouse thương mại</td>
                </tr>
                <tr style="border-bottom:1px solid #e2e8f0;">
                    <td style="padding:12px 18px; font-weight:700; color:#0b2246; background:#f8fafc;">Phong cách kiến trúc</td>
                    <td style="padding:12px 18px; color:#334155;">Cổ điển hoàng gia Châu Âu, Baroque &amp; Tân cổ điển Pháp thế kỷ 18</td>
                </tr>
                <tr style="border-bottom:1px solid #e2e8f0;">
                    <td style="padding:12px 18px; font-weight:700; color:#0b2246; background:#f8fafc;">Hình thức sở hữu</td>
                    <td style="padding:12px 18px; color:#334155;">Sổ đỏ lâu dài từng lô (Quyền sở hữu vĩnh viễn)</td>
                </tr>
                <tr>
                    <td style="padding:12px 18px; font-weight:700; color:#0b2246; background:#f8fafc;">Thời điểm mở bán</td>
                    <td style="padding:12px 18px; color:#d4af37; font-weight:800;">Năm 2026 (Đang nhận đặt chỗ ưu tiên vị trí đẹp đợt 1)</td>
                </tr>
            </tbody>
        </table>
    </section>

    <!-- SECTION 2: VỊ TRÍ & KẾT NỐI GIAO THÔNG -->
    <section id="vi-tri" style="margin-top: 50px;">
        <h2 class="section-title-centered" style="font-size:26px; font-weight:800; color:#0b2246; text-transform:uppercase;">Vị Trí Đất Rồng Long Biên &amp; Mạng Lưới Kết Nối</h2>
        <p style="font-size:15.5px; line-height:1.8; color:#334155; text-align:justify;">
            Tọa lạc tại vùng lõi phong thủy thịnh vượng bậc nhất bờ Đông Thủ đô, <strong>dự án Noble Palace Long Bien</strong> hội tụ trọn vẹn mạch nguồn sinh khí từ hai con sông lớn là sông Hồng và sông Đuống. Từ dự án, cư dân dễ dàng di chuyển nhanh chóng vào khu trung tâm Hoàn Kiếm qua các cây cầu trọng điểm cũng như thuận tiện di chuyển liên tỉnh:
        </p>

        <!-- 3 KHỐI THỜI GIAN DI CHUYỂN -->
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:20px; margin:30px 0;">
            <div style="background:#ffffff; border:1px solid #e2e8f0; border-top:4px solid #d4af37; border-radius:12px; padding:24px 20px; box-shadow:0 4px 16px rgba(0,0,0,0.06);">
                <div style="font-size:24px; font-weight:800; color:#d4af37; margin-bottom:8px;">5 – 10 PHÚT</div>
                <div style="font-size:15px; font-weight:700; color:#0b2246; margin-bottom:10px;">Trung Tâm Hoàn Kiếm &amp; Tiện Ích Long Biên</div>
                <ul style="margin:0; padding-left:18px; font-size:14px; color:#475569; line-height:1.7;">
                    <li>Hồ Hoàn Kiếm &amp; Phố Cổ Hà Nội qua cầu Chương Dương / Long Biên</li>
                    <li>Trung tâm thương mại Tasco Mall Long Biên</li>
                    <li>Sân Golf Long Biên 27 hố chuẩn quốc tế</li>
                    <li>Công viên sinh thái Long Biên rợp bóng cây xanh</li>
                </ul>
            </div>

            <div style="background:#ffffff; border:1px solid #e2e8f0; border-top:4px solid #0b2246; border-radius:12px; padding:24px 20px; box-shadow:0 4px 16px rgba(0,0,0,0.06);">
                <div style="font-size:24px; font-weight:800; color:#0b2246; margin-bottom:8px;">10 – 15 PHÚT</div>
                <div style="font-size:15px; font-weight:700; color:#0b2246; margin-bottom:10px;">Đại Siêu Thị &amp; Y Tế Giáo Dục Quốc Tế</div>
                <ul style="margin:0; padding-left:18px; font-size:14px; color:#475569; line-height:1.7;">
                    <li>Đại siêu thị Aeon Mall Long Biên lớn nhất miền Bắc</li>
                    <li>Bệnh viện Đa khoa Quốc tế Tâm Anh, Vinmec</li>
                    <li>Hệ thống trường liên cấp quốc tế BIS, Wellspring</li>
                    <li>Kết nối tuyến Vành đai 2, Vành đai 3 và cao tốc Hà Nội - Hải Phòng</li>
                </ul>
            </div>

            <div style="background:#ffffff; border:1px solid #e2e8f0; border-top:4px solid #b8860b; border-radius:12px; padding:24px 20px; box-shadow:0 4px 16px rgba(0,0,0,0.06);">
                <div style="font-size:24px; font-weight:800; color:#b8860b; margin-bottom:8px;">20 PHÚT</div>
                <div style="font-size:15px; font-weight:700; color:#0b2246; margin-bottom:10px;">Cửa Ngõ Hàng Không Quốc Tế</div>
                <ul style="margin:0; padding-left:18px; font-size:14px; color:#475569; line-height:1.7;">
                    <li>Sân bay Quốc tế Nội Bài qua cầu Đông Trù và đường Võ Nguyên Giáp</li>
                    <li>Kết nối thông suốt tam giác kinh tế Hà Nội – Hải Phòng – Quảng Ninh</li>
                    <li>Khu công nghệ cao Bắc Thăng Long và các tỉnh vệ tinh</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- SECTION 3: TIỆN ÍCH HOÀNG GIA -->
    <section id="tien-ich" style="margin-top: 50px;">
        <h2 class="section-title-centered" style="font-size:26px; font-weight:800; color:#0b2246; text-transform:uppercase;">Hệ Sinh Thái Tiện Ích Thượng Lưu Độc Bản</h2>
        <p style="font-size:15.5px; line-height:1.8; color:#334155; text-align:justify;">
            Tại <strong>Noble Palace Long Biên</strong>, mỗi ngày trôi qua là một hành trình tận hưởng nghỉ dưỡng chuẩn vương giả. Dự án được quy hoạch đồng bộ với chuỗi tiện ích "All-in-one" khép kín, an ninh đa lớp 24/7 và dịch vụ đặc quyền quản gia riêng:
        </p>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin:25px 0;">
            <div>
                <img src="https://sunshine-group.com.vn/wp-content/uploads/2026/11/cong-vien-vom-kinh-chau-au-noble-palace-long-bien.jpg" alt="Công viên vòm kính Châu Âu Noble Palace Long Biên" style="width:100%; border-radius:10px; box-shadow:0 6px 18px rgba(0,0,0,0.1);" />
                <p style="font-size:13px; color:#64748b; font-style:italic; text-align:center; margin-top:6px;">Công viên vòm kính châu Âu và đài phun nước trung tâm</p>
            </div>
            <div>
                <img src="https://sunshine-group.com.vn/wp-content/uploads/2026/11/lounge-bar-thuong-luu-noble-palace-long-bien.jpg" alt="Lounge bar thượng lưu Noble Palace Long Biên" style="width:100%; border-radius:10px; box-shadow:0 6px 18px rgba(0,0,0,0.1);" />
                <p style="font-size:13px; color:#64748b; font-style:italic; text-align:center; margin-top:6px;">Lounge bar &amp; Cigar club sang trọng dành riêng cho quý chủ nhân</p>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin:25px 0;">
            <div>
                <img src="https://sunshine-group.com.vn/wp-content/uploads/2026/11/ho-boi-resort-trung-tam-noble-palace-long-bien.jpg" alt="Hồ bơi resort trung tâm Noble Palace Long Biên" style="width:100%; border-radius:10px; box-shadow:0 6px 18px rgba(0,0,0,0.1);" />
                <p style="font-size:13px; color:#64748b; font-style:italic; text-align:center; margin-top:6px;">Hồ bơi phong cách resort nhiệt đới giữa lòng khu dinh thự</p>
            </div>
            <div>
                <img src="https://sunshine-group.com.vn/wp-content/uploads/2026/11/be-boi-san-vuon-biet-thu-noble-palace-long-bien.jpg" alt="Bể bơi sân vườn biệt thự Noble Palace Long Biên" style="width:100%; border-radius:10px; box-shadow:0 6px 18px rgba(0,0,0,0.1);" />
                <p style="font-size:13px; color:#64748b; font-style:italic; text-align:center; margin-top:6px;">Bể bơi và không gian thư giãn riêng tư tại từng căn biệt thự</p>
            </div>
        </div>
    </section>

    <!-- SECTION 4: SẢN PHẨM & MẶT BẰNG -->
    <section id="san-pham" style="margin-top: 50px;">
        <h2 class="section-title-centered" style="font-size:26px; font-weight:800; color:#0b2246; text-transform:uppercase;">Bộ Sưu Tập Dinh Thự &amp; Shophouse Quý Tộc</h2>
        <p style="font-size:15.5px; line-height:1.8; color:#334155; text-align:justify;">
            Mỗi căn biệt thự tại <strong>biệt thự Noble Palace Long Biên</strong> là một tác phẩm nghệ thuật điêu khắc đá tinh xảo, trần nhà cao thoáng đạt, kính Low-E tràn viền cách âm cách nhiệt và sân vườn rộng mở hòa quyện cùng thiên nhiên:
        </p>

        <div style="margin:25px 0; text-align:center;">
            <img src="https://sunshine-group.com.vn/wp-content/uploads/2026/11/day-biet-thu-song-lap-noble-palace-long-bien.jpg" alt="Dãy biệt thự song lập Noble Palace Long Biên" style="width:100%; max-width:1100px; border-radius:12px; box-shadow:0 8px 24px rgba(0,0,0,0.1);" />
            <p style="font-size:13.5px; color:#64748b; font-style:italic; margin-top:8px;">Dãy biệt thự song lập và đơn lập phong cách lâu đài Pháp uy nghi</p>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:20px; margin:25px 0;">
            <div style="background:#f8fafc; border:1px solid #cbd5e1; border-radius:10px; padding:20px;">
                <h4 style="color:#0b2246; font-size:17px; font-weight:800; margin-bottom:8px;">Dinh Thự Đơn Lập</h4>
                <p style="font-size:14px; color:#475569; margin:0; line-height:1.6;">Diện tích từ 300m² – 500m², 4 mặt thoáng, có hồ bơi riêng, hầm rượu, rạp chiếu phim tại gia và sân vườn bao quanh.</p>
            </div>
            <div style="background:#f8fafc; border:1px solid #cbd5e1; border-radius:10px; padding:20px;">
                <h4 style="color:#0b2246; font-size:17px; font-weight:800; margin-bottom:8px;">Biệt Thự Song Lập</h4>
                <p style="font-size:14px; color:#475569; margin:0; line-height:1.6;">Diện tích từ 180m² – 250m², thiết kế 4 tầng 1 tum đối xứng hoàn mỹ, ban công hoa sắt uốn nghệ thuật tinh tế.</p>
            </div>
            <div style="background:#f8fafc; border:1px solid #cbd5e1; border-radius:10px; padding:20px;">
                <h4 style="color:#0b2246; font-size:17px; font-weight:800; margin-bottom:8px;">Shophouse Đại Lộ</h4>
                <p style="font-size:14px; color:#475569; margin:0; line-height:1.6;">Diện tích từ 120m² – 160m², mặt tiền đại lộ rộng lớn, vỉa hè thênh thang kinh doanh ẩm thực cao cấp, thời trang hàng hiệu.</p>
            </div>
        </div>

        <div style="margin:25px 0; text-align:center;">
            <img src="https://sunshine-group.com.vn/wp-content/uploads/2026/11/dai-lo-thuong-mai-shophouse-noble-palace-long-bien.jpg" alt="Đại lộ thương mại shophouse Noble Palace Long Biên" style="width:100%; max-width:1100px; border-radius:12px; box-shadow:0 8px 24px rgba(0,0,0,0.1);" />
            <p style="font-size:13.5px; color:#64748b; font-style:italic; margin-top:8px;">Tuyến phố thương mại sầm uất quy tụ các thương hiệu thời trang và ẩm thực Michelin</p>
        </div>
    </section>

    <!-- SECTION 5: CHÍNH SÁCH BÁN HÀNG -->
    <section id="chinh-sach" style="margin-top: 50px;">
        <h2 class="section-title-centered" style="font-size:26px; font-weight:800; color:#0b2246; text-transform:uppercase;">Chính Sách Bán Hàng &amp; Tiến Độ Thanh Toán 2026</h2>
        <div style="background:#fffdf5; border:1px solid #d4af37; border-radius:12px; padding:25px 30px; margin:25px 0;">
            <h3 style="color:#b8860b; font-size:19px; font-weight:800; margin-bottom:12px;">🌟 Ưu Đãi Độc Quyền Dành Cho Khách Hàng Booking Sớm:</h3>
            <ul style="font-size:15px; color:#334155; line-height:1.8; margin:0; padding-left:20px;">
                <li>Chiết khấu lên tới <strong>8% - 10%</strong> giá trị biệt thự khi thanh toán sớm 95%.</li>
                <li>Hỗ trợ vay vốn ngân hàng tới <strong>70% giá trị hợp đồng</strong>, hỗ trợ lãi suất <strong>0% trong 24 tháng</strong>.</li>
                <li>Tặng gói hoàn thiện cảnh quan sân vườn hoàng gia trị giá <strong>500 triệu đồng</strong>.</li>
                <li>Miễn phí dịch vụ quản lý vận hành tiêu chuẩn khách sạn 5 sao trong <strong>03 năm đầu</strong>.</li>
            </ul>
        </div>
    </section>

    <!-- SECTION 6: HỎI ĐÁP -->
    <section id="hoi-dap" style="margin-top: 50px;">
        <h2 class="section-title-centered" style="font-size:26px; font-weight:800; color:#0b2246; text-transform:uppercase;">Câu Hỏi Thường Gặp (FAQ) Về Noble Palace Long Bien</h2>
        <div style="margin:25px 0;">
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:18px 22px; margin-bottom:12px;">
                <h4 style="color:#0b2246; font-size:16px; font-weight:700; margin-bottom:6px;">Dự án Noble Palace Long Bien nằm ở vị trí nào tại Long Biên?</h4>
                <p style="font-size:14.5px; color:#475569; margin:0; line-height:1.6;">Dự án tọa lạc tại vị trí chiến lược thuộc quận Long Biên, kề cận trục đường huyết mạch kết nối cầu Chương Dương, cầu Long Biên và cầu Vĩnh Tuy, chỉ mất 10 phút sang phố cổ Hoàn Kiếm và 20 phút tới sân bay Nội Bài.</p>
            </div>
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:18px 22px; margin-bottom:12px;">
                <h4 style="color:#0b2246; font-size:16px; font-weight:700; margin-bottom:6px;">Pháp lý dự án Noble Palace Long Bien có sở hữu lâu dài không?</h4>
                <p style="font-size:14.5px; color:#475569; margin:0; line-height:1.6;">100% sản phẩm dinh thự, biệt thự và shophouse tại Noble Palace Long Bien đều được cấp Giấy chứng nhận quyền sử dụng đất và tài sản gắn liền với đất (Sổ hồng/Sổ đỏ) sở hữu lâu dài vĩnh viễn.</p>
            </div>
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:18px 22px;">
                <h4 style="color:#0b2246; font-size:16px; font-weight:700; margin-bottom:6px;">Làm thế nào để nhận bảng giá gốc và tham quan nhà mẫu?</h4>
                <p style="font-size:14.5px; color:#475569; margin:0; line-height:1.6;">Quý khách vui lòng điền thông tin vào form đăng ký nhận bảng giá phía dưới hoặc liên hệ Hotline 24/7: <strong>0942.655.111</strong> để được bộ phận tư vấn F1 gửi tài liệu chính thức qua Zalo trong 5 phút.</p>
            </div>
        </div>
    </section>

    <!-- FORM ĐĂNG KÝ CHÂN TRANG THẤP TẦNG -->
    <div id="nhan-bang-gia" style="margin-top: 50px;">
        [kbds_lead_form_thap_tang title="ĐĂNG KÝ NHẬN TRỌN BỘ TÀI LIỆU GỐC &amp; BẢNG GIÁ NOBLE PALACE LONG BIEN 2026" subtitle="Bản đồ quy hoạch 1/500, bảng giá ngoại giao &amp; bảng tính dòng tiền vay ngân hàng qua Zalo:" project="Noble Palace Long Bien"]
    </div>

</div>
HTML;

// 1. Lưu Local Post Type du_an
$existing_pillar = get_page_by_path($pillar_slug, OBJECT, 'du_an');
$pillar_post_data = [
    'post_title'   => $pillar_title,
    'post_name'    => $pillar_slug,
    'post_type'    => 'du_an',
    'post_status'  => 'publish',
    'post_content' => $content_pillar
];

if ($existing_pillar) {
    $pillar_post_data['ID'] = $existing_pillar->ID;
    $local_pillar_id = wp_update_post($pillar_post_data);
    echo "  -> Đã cập nhật Local Pillar ID: $local_pillar_id\n";
} else {
    $local_pillar_id = wp_insert_post($pillar_post_data);
    echo "  -> Đã tạo mới Local Pillar ID: $local_pillar_id\n";
}

// Meta SEO Cột Trụ
update_post_meta($local_pillar_id, 'rank_math_title', 'Dự Án Noble Palace Long Biên | Bảng Giá Biệt Thự Gốc CĐT 2026');
update_post_meta($local_pillar_id, 'rank_math_focus_keyword', 'Noble Palace Long Bien, dự án Noble Palace Long Bien, biệt thự Noble Palace Long Biên, Noble Palace Long Biên');
update_post_meta($local_pillar_id, 'rank_math_description', 'Dự án Noble Palace Long Bien (Long Biên, Hà Nội) của Sunshine Group. Quần thể dinh thự, biệt thự & shophouse hoàng gia 2026 ven sông Hồng, bảng giá gốc CĐT.');
update_post_meta($local_pillar_id, 'rank_math_pillar_content', 'on');
update_post_meta($local_pillar_id, 'rank_math_canonical_url', $pillar_url);

// 2. Đồng bộ Pillar lên Live Server
$live_check = $sync->request("/wp/v2/du_an?slug={$pillar_slug}");
$live_content = str_replace('http://localhost/www/Sunshinegroup/', 'https://sunshine-group.com.vn/', $content_pillar);

$pillar_live_payload = [
    'title'   => $pillar_title,
    'slug'    => $pillar_slug,
    'content' => $live_content,
    'status'  => 'publish',
    'meta'    => [
        'rank_math_title'          => 'Dự Án Noble Palace Long Biên | Bảng Giá Biệt Thự Gốc CĐT 2026',
        'rank_math_focus_keyword'  => 'Noble Palace Long Bien, dự án Noble Palace Long Bien, biệt thự Noble Palace Long Biên, Noble Palace Long Biên',
        'rank_math_description'    => 'Dự án Noble Palace Long Bien (Long Biên, Hà Nội) của Sunshine Group. Quần thể dinh thự, biệt thự & shophouse hoàng gia 2026 ven sông Hồng, bảng giá gốc CĐT.',
        'rank_math_pillar_content' => 'on',
        'rank_math_canonical_url'  => $pillar_url
    ]
];

if (!empty($live_check['body']) && is_array($live_check['body'])) {
    $live_pillar_id = $live_check['body'][0]['id'];
    $res = $sync->request("/wp/v2/du_an/{$live_pillar_id}", 'POST', $pillar_live_payload);
    echo "  [LIVE OK] Cập nhật Live Pillar ID: {$live_pillar_id}\n";
} else {
    $res = $sync->request("/wp/v2/du_an", 'POST', $pillar_live_payload);
    $live_pillar_id = $res['body']['id'] ?? 'ERR';
    echo "  [LIVE TẠO MỚI] Tạo mới Live Pillar ID: {$live_pillar_id}\n";
}

echo "\n=== 2. TẠO CÁC BÀI VIẾT VỆ TINH (CLUSTER CẤP 3) CHỐNG ĂN THỊT TỪ KHÓA ===\n";

$satellites = [
    [
        'slug'        => 'danh-gia-vi-tri-du-an-noble-palace-long-bien',
        'title'       => 'Đánh Giá Vị Trí Dự Án Noble Palace Long Biên: Tọa Độ Vàng Đất Rồng Phía Đông Thủ Đô [2026]',
        'focuskw'     => 'vị trí dự án noble palace long biên',
        'desc'        => 'Đánh giá chi tiết vị trí dự án Noble Palace Long Biên tọa độ vàng đất rồng giao hòa sông Hồng - sông Đuống. Kết nối phố cổ 10 phút, sân bay Nội Bài 20 phút.',
        'content'     => <<<HTML
<div style="background:#f8fafc; border-left:4px solid #d4af37; padding:16px 20px; border-radius:4px; margin-bottom:25px; font-size:15.5px; line-height:1.7;">
    Bài viết phân tích chuyên sâu thuộc hệ thống thông tin quy hoạch cho <a href="{$pillar_url}" title="Dự án Noble Palace Long Bien" style="color:#d4af37; font-weight:700; text-decoration:underline;">Noble Palace Long Bien</a> – quần thể dinh thự hoàng gia ven sông Hồng được phát triển bởi Tập đoàn Sunshine.
</div>

<p style="font-size:16px; line-height:1.8; color:#334155; text-align:justify;">
    Trong bản đồ quy hoạch phát triển đô thị tương lai của Thủ đô Hà Nội, khu vực phía Đông đang vươn mình trở thành cực tăng trưởng năng động và sầm uất bậc nhất. Tọa lạc kiêu hãnh trên dải đất phù sa màu mỡ “Đất rồng Long Biên”, <strong>vị trí dự án Noble Palace Long Biên</strong> không chỉ sở hữu giá trị phong thủy hưng vượng hiếm có mà còn nắm giữ tọa độ kết nối kim cương, mang lại tiềm năng gia tăng giá trị phi mã trong năm 2026.
</p>

<h2 style="color:#0b2246; font-size:22px; font-weight:800; margin-top:35px;">1. Tọa Độ Vàng Nơi Hai Dòng Sông Huyết Mạch Giao Hòa</h2>
<p style="font-size:15.5px; line-height:1.8; color:#334155; text-align:justify;">
    Người xưa có câu “Nhất cận thị, nhị cận giang, tam cận lộ”. <strong>Vị trí dự án Noble Palace Long Biên</strong> trọn vẹn cả ba yếu tố hoàng kim đó. Nằm kề cận nơi hợp lưu giữa hai dòng sông huyền thoại là <strong>sông Hồng và sông Đuống</strong>, dự án đón trọn luồng sinh khí thủy mộc mát lành, mang lại vượng khí tài lộc và sự trường tồn cho các gia tộc quý tộc.
</p>

<div style="margin:25px 0; text-align:center;">
    <img src="https://sunshine-group.com.vn/wp-content/uploads/2026/11/canh-quan-xanh-kien-truc-co-dien-noble-palace-long-bien.jpg" alt="Cảnh quan vị trí dự án Noble Palace Long Biên" style="width:100%; max-width:900px; border-radius:10px; box-shadow:0 6px 20px rgba(0,0,0,0.1);" />
    <p style="font-size:13.5px; color:#64748b; font-style:italic; margin-top:6px;">Không gian cảnh quan xanh mát và kiến trúc quý tộc tại Noble Palace Long Biên</p>
</div>

<h2 style="color:#0b2246; font-size:22px; font-weight:800; margin-top:35px;">2. Khả Năng Kết Nối Giao Thông Siêu Tốc</h2>
<p style="font-size:15.5px; line-height:1.8; color:#334155; text-align:justify;">
    Hưởng lợi từ hạ tầng giao thông nghìn tỷ phía Đông Hà Nội với các cây cầu hiện hữu và tương lai, cư dân Noble Palace Long Biên dễ dàng di chuyển:
</p>
<ul style="font-size:15px; line-height:1.8; color:#334155; padding-left:22px;">
    <li><strong>Chỉ 5 – 10 phút:</strong> Đến trung tâm Hồ Hoàn Kiếm, phố cổ Hà Nội, TTTM Tasco Mall, sân Golf Long Biên 27 hố và công viên sinh thái Long Biên.</li>
    <li><strong>Chỉ 10 – 15 phút:</strong> Tiếp cận đại siêu thị Aeon Mall Long Biên, bệnh viện đa khoa quốc tế Tâm Anh, trường quốc tế BIS.</li>
    <li><strong>Chưa đầy 20 phút:</strong> Di chuyển thẳng tới Sân bay Quốc tế Nội Bài qua đường Võ Nguyên Giáp.</li>
    <li><strong>Kết nối vùng kinh tế:</strong> Nằm trên trục tam giác kinh tế trọng điểm phía Bắc: Hà Nội – Hải Phòng – Quảng Ninh qua cao tốc 5B hiện đại.</li>
</ul>

<div style="margin:25px 0; text-align:center;">
    <img src="https://sunshine-group.com.vn/wp-content/uploads/2026/11/dai-lo-thuong-mai-shophouse-noble-palace-long-bien.jpg" alt="Mạng lưới giao thông và shophouse Noble Palace Long Biên" style="width:100%; max-width:900px; border-radius:10px; box-shadow:0 6px 20px rgba(0,0,0,0.1);" />
    <p style="font-size:13.5px; color:#64748b; font-style:italic; margin-top:6px;">Trục đại lộ giao thông rộng mở thuận tiện kinh doanh và kết nối</p>
</div>

<h2 style="color:#0b2246; font-size:22px; font-weight:800; margin-top:35px;">3. Đòn Bẩy Quy Hoạch Và Hạ Tầng Phía Đông 2026</h2>
<p style="font-size:15.5px; line-height:1.8; color:#334155; text-align:justify;">
    Với định hướng quy hoạch đô thị hai bên bờ sông Hồng và việc triển khai các cây cầu mới như cầu Tứ Liên, cầu Trần Hưng Đạo, quận Long Biên đang bứt phá mạnh mẽ thành trung tâm hành chính - thương mại mới của Thủ đô. Sở hữu một sản phẩm tại <strong>vị trí dự án Noble Palace Long Biên</strong> chính là bảo chứng vững chắc cho giá trị gia tăng bất động sản bền vững theo thời gian.
</p>

[kbds_lead_form_thap_tang title="ĐĂNG KÝ NHẬN PHÂN TÍCH VỊ TRÍ &amp; BẢNG GIÁ ƯU ĐÃI 2026" subtitle="Nhận trọn bộ bản đồ quy hoạch 1/500 và bảng tính lãi vay ngân hàng trực tiếp qua Zalo:" project="Noble Palace Long Bien"]
HTML
    ],
    [
        'slug'        => 'kien-truc-tien-ich-dinh-thu-noble-palace-long-bien',
        'title'       => 'Kiến Trúc Hoàng Gia &amp; Tiện Ích Độc Bản Tại Noble Palace Long Biên [2026]',
        'focuskw'     => 'tiện ích dự án noble palace long biên',
        'desc'        => 'Khám phá kiến trúc hoàng gia Pháp Baroque và hệ thống tiện ích dự án Noble Palace Long Biên: công viên vòm kính, hồ bơi resort, lounge bar quý tộc 2026.',
        'content'     => <<<HTML
<div style="background:#f8fafc; border-left:4px solid #d4af37; padding:16px 20px; border-radius:4px; margin-bottom:25px; font-size:15.5px; line-height:1.7;">
    Bài viết nằm trong chuỗi phân tích sản phẩm cho <a href="{$pillar_url}" title="Dự án Noble Palace Long Bien" style="color:#d4af37; font-weight:700; text-decoration:underline;">dự án Noble Palace Long Bien</a> – biểu tượng biệt thự phong cách quý tộc châu Âu bên bờ sông Hồng.
</div>

<p style="font-size:16px; line-height:1.8; color:#334155; text-align:justify;">
    Không chỉ thu hút giới siêu giàu bởi vị trí đất vàng đắc địa, <strong>Noble Palace Long Bien</strong> còn xác lập một chuẩn mực sống xa xỉ đỉnh cao nhờ ngôn ngữ kiến trúc Pháp cổ điển Baroque lộng lẫy kết hợp cùng chuỗi <strong>tiện ích dự án Noble Palace Long Biên</strong> độc quyền mà hiếm dự án nào tại Thủ đô có được.
</p>

<h2 style="color:#0b2246; font-size:22px; font-weight:800; margin-top:35px;">1. Kiến Trúc Baroque Pháp – Bản Tuyên Ngôn Của Đẳng Cấp Vương Giả</h2>
<p style="font-size:15.5px; line-height:1.8; color:#334155; text-align:justify;">
    Lấy cảm hứng từ những lâu đài Versailles tráng lệ, các căn dinh thự tại Noble Palace Long Biên được trau chuốt tỉ mỉ từng chi tiết hoa văn đắp nổi, thức cột Ionic uy nghiêm và mái Mansard quyền quý. Toàn bộ mặt ngoài được ốp đá tự nhiên sang trọng, mang đến vẻ đẹp trường tồn cùng thời gian.
</p>

<div style="margin:25px 0; text-align:center;">
    <img src="https://sunshine-group.com.vn/wp-content/uploads/2026/11/chi-tiet-hoa-van-kien-truc-noble-palace-long-bien.jpg" alt="Chi tiết hoa văn kiến trúc biệt thự Noble Palace Long Biên" style="width:100%; max-width:900px; border-radius:10px; box-shadow:0 6px 20px rgba(0,0,0,0.1);" />
    <p style="font-size:13.5px; color:#64748b; font-style:italic; margin-top:6px;">Đá tự nhiên nguyên khối và hoa văn điêu khắc thủ công tinh xảo</p>
</div>

<h2 style="color:#0b2246; font-size:22px; font-weight:800; margin-top:35px;">2. Chuỗi Tiện Ích Dự Án Noble Palace Long Biên: Nghỉ Dưỡng Tại Gia</h2>
<p style="font-size:15.5px; line-height:1.8; color:#334155; text-align:justify;">
    Hệ sinh thái <strong>tiện ích dự án Noble Palace Long Biên</strong> được thiết kế theo mô hình "Resort Living" riêng tư tuyệt đối:
</p>
<ul style="font-size:15px; line-height:1.8; color:#334155; padding-left:22px;">
    <li><strong>Công viên vòm kính nghệ thuật:</strong> Điểm nhấn kiến trúc châu Âu lộng lẫy giữa lòng dự án, nơi tổ chức các sự kiện dạ tiệc quý tộc ngoài trời.</li>
    <li><strong>Hồ bơi resort trung tâm &amp; Bể bơi gia đình:</strong> Hệ thống lọc nước điện phân muối khoáng cao cấp, tạo không gian thư giãn mát lành riêng tư.</li>
    <li><strong>Lounge Bar &amp; Wine Cellar:</strong> Không gian tiếp đãi đối tác sang trọng với bộ sưu tập rượu vang và xì gà thượng hạng.</li>
    <li><strong>Hệ thống quản gia riêng &amp; An ninh 5 lớp:</strong> Camera AI nhận diện khuôn mặt, đội ngũ an ninh tuần tra 24/7 bảo đảm sự riêng tư tối thượng cho chủ nhân.</li>
</ul>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin:25px 0;">
    <div>
        <img src="https://sunshine-group.com.vn/wp-content/uploads/2026/11/cong-vien-vom-kinh-chau-au-noble-palace-long-bien.jpg" alt="Công viên vòm kính Noble Palace Long Biên" style="width:100%; border-radius:10px; box-shadow:0 6px 18px rgba(0,0,0,0.1);" />
        <p style="font-size:13px; color:#64748b; font-style:italic; text-align:center; margin-top:6px;">Công viên vòm kính trung tâm</p>
    </div>
    <div>
        <img src="https://sunshine-group.com.vn/wp-content/uploads/2026/11/be-boi-san-vuon-biet-thu-noble-palace-long-bien.jpg" alt="Bể bơi sân vườn Noble Palace Long Biên" style="width:100%; border-radius:10px; box-shadow:0 6px 18px rgba(0,0,0,0.1);" />
        <p style="font-size:13px; color:#64748b; font-style:italic; text-align:center; margin-top:6px;">Bể bơi sân vườn thư thái tại gia</p>
    </div>
</div>

<h2 style="color:#0b2246; font-size:22px; font-weight:800; margin-top:35px;">3. Tận Hưởng Cuộc Sống Thượng Lưu Đích Thực</h2>
<p style="font-size:15.5px; line-height:1.8; color:#334155; text-align:justify;">
    Không đơn thuần là chốn đi về, mỗi căn dinh thự tại đây là một tài sản truyền đời danh giá. Sự cộng hưởng giữa kiến trúc đỉnh cao và hệ sinh thái <strong>tiện ích dự án Noble Palace Long Biên</strong> mang lại trải nghiệm sống vượt xa kỳ vọng của tầng lớp tinh hoa.
</p>

[kbds_lead_form_thap_tang title="ĐĂNG KÝ THAM QUAN BIỆT THỰ MẪU &amp; NHẬN BẢNG GIÁ 2026" subtitle="Nhận vé mời VIP tham quan thực tế và giỏ hàng ngoại giao đợt 1 qua Zalo:" project="Noble Palace Long Bien"]
HTML
    ]
];

foreach ($satellites as $sat) {
    $sat_slug = $sat['slug'];
    $sat_title = $sat['title'];
    $sat_content = $sat['content'];
    
    // 1. Lưu Local Post
    $existing = get_page_by_path($sat_slug, OBJECT, 'post');
    $post_data = [
        'post_title'   => $sat_title,
        'post_name'    => $sat_slug,
        'post_type'    => 'post',
        'post_status'  => 'publish',
        'post_content' => $sat_content
    ];
    
    if ($existing) {
        $post_data['ID'] = $existing->ID;
        $local_id = wp_update_post($post_data);
        echo "  [LOCAL VỆ TINH] Cập nhật bài: \"$sat_title\" (ID $local_id)\n";
    } else {
        $local_id = wp_insert_post($post_data);
        echo "  [LOCAL VỆ TINH] Tạo mới bài: \"$sat_title\" (ID $local_id)\n";
    }
    
    // Meta SEO Vệ Tinh (Chống ăn thịt)
    update_post_meta($local_id, 'rank_math_title', $sat_title);
    update_post_meta($local_id, 'rank_math_focus_keyword', $sat['focuskw']);
    update_post_meta($local_id, 'rank_math_description', $sat['desc']);
    update_post_meta($local_id, 'rank_math_pillar_content', 'off'); // Tắt cờ Pillar
    update_post_meta($local_id, 'rank_math_canonical_url', 'https://sunshine-group.com.vn/' . $sat_slug . '/');
    
    // 2. Đồng bộ lên Live Server
    $live_check = $sync->request("/wp/v2/posts?slug={$sat_slug}");
    $live_post_content = str_replace('http://localhost/www/Sunshinegroup/', 'https://sunshine-group.com.vn/', $sat_content);
    
    $live_post_payload = [
        'title'   => $sat_title,
        'slug'    => $sat_slug,
        'content' => $live_post_content,
        'status'  => 'publish',
        'meta'    => [
            'rank_math_title'          => $sat_title,
            'rank_math_focus_keyword'  => $sat['focuskw'],
            'rank_math_description'    => $sat['desc'],
            'rank_math_pillar_content' => 'off',
            'rank_math_canonical_url'  => 'https://sunshine-group.com.vn/' . $sat_slug . '/'
        ]
    ];
    
    if (!empty($live_check['body']) && is_array($live_check['body'])) {
        $live_post_id = $live_check['body'][0]['id'];
        $sync->request("/wp/v2/posts/{$live_post_id}", 'POST', $live_post_payload);
        echo "    └─ [LIVE VỆ TINH OK] Cập nhật Live ID: {$live_post_id}\n";
    } else {
        $res = $sync->request("/wp/v2/posts", 'POST', $live_post_payload);
        $live_post_id = $res['body']['id'] ?? 'ERR';
        echo "    └─ [LIVE VỆ TINH TẠO MỚI] Tạo mới Live ID: {$live_post_id}\n";
    }
}

echo "\n=== HOÀN TẤT KHỞI TẠO DỰ ÁN & VỆ TINH NOBLE PALACE LONG BIEN! ===\n";
