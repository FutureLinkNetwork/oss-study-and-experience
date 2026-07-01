@extends('default.www.layout')

@section('title', '事業者登録について ｜ 習い事クーポン管理システム')
@section('body_id', 'other')

@section('content')

		
		<div class="bg_content_wrap">
			<div class="bg_content_inner">

				<!-- article -->
				<section id="sec_article">
					<!-- breadcrumb -->
					<ul class="uk-breadcrumb">
						<li><a href="./">TOP</a></li>
						<li><span>事業者登録について</span></li>
					</ul>

					<div class="uk-container">
						<h2 class="uk-h2 uk-text-center"><span class="ttl anime">事業者登録について</span></h2>
						<div class="content_wrap uk-clearfix anime" uk-grid>
							<div class="content_inner uk-flex-wrap-stretch">

								<div class="uk-margin-large-bottom">

								
									<!-- 本文内見出し（大） -->
									<h4 class="uk-h4">本文内見出し</h4>
									<!-- / 本文内見出し（大） -->

									<!-- 本文テキスト（中） -->
									<p>本文テキスト</p>
									<!-- / 本文テキスト（中） -->


										<div class="btn_area uk-flex-center">
												<div class="uk-text-center" ><a href="/business_form" target="_blank"><span>事業者登録申請フォーム</span></a></div>
										</div>

									<!-- 本文テキスト（中） -->
									 <br>								
								</div>
							</div>
						</div>
					</div>
				</section>
			</div>
		</div>
<style>
  /* ========================================
     子どもの習い事応援事業
     ======================================== */

  *,
  *::before,
  *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }

  body {
    font-family: "Noto Sans JP", sans-serif;
    font-size: 16px;
    line-height: 1.8;
    color: #3e3e3e;
    background-color: #f2f4ee;
    -webkit-font-smoothing: antialiased;
  }

  .l-wrapper {
    max-width: 900px;
    margin: 40px auto;
    padding: 0 20px;
  }

  /* セクション見出し（サイトのh2スタイル準拠） */
  .section-title {
    font-size: 24px;
    font-weight: 500;
    color: #3e3e3e;
    text-align: center;
    margin-bottom: 40px;
    letter-spacing: 0.05em;
  }

  /* テーブル全体 */
  .doc-table {
    width: 100%;
    border-collapse: collapse;
    background-color: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  }

  /* ヘッダー行 */
  .doc-table thead th {
    background-color: #3e3e3e;
    color: #fff;
    font-weight: 500;
    font-size: 15px;
    padding: 14px 20px;
    text-align: left;
    letter-spacing: 0.05em;
  }

  .doc-table thead th:first-child {
    width: 120px;
    text-align: center;
  }

  /* カテゴリセル（左列） */
  .doc-table .category-cell {
    background-color: #f9f8f6;
    font-weight: 700;
    font-size: 15px;
    text-align: center;
    vertical-align: middle;
    padding: 16px 12px;
    border-bottom: 1px solid #e8e6e3;
    letter-spacing: 0.08em;
    white-space: nowrap;
  }

  /* 内容セル（右列） */
  .doc-table .content-cell {
    padding: 16px 20px;
    border-bottom: 1px solid #e8e6e3;
    vertical-align: top;
    font-size: 15px;
    line-height: 1.75;
  }

  /* 最終行のボーダー除去 */
  .doc-table tbody tr:last-child .category-cell,
  .doc-table tbody tr:last-child .content-cell {
    border-bottom: none;
  }

  /* 書類名（下線付き） */
  .doc-name {
    text-decoration: underline;
    text-underline-offset: 3px;
    font-weight: 500;
  }

  /* 補足テキスト */
  .doc-note {
    display: block;
    margin-top: 4px;
    font-size: 14px;
    color: #666;
    line-height: 1.7;
  }

  /* 「ただし〜」の注記 */
  .doc-exception {
    display: block;
    margin-top: 6px;
    font-size: 14px;
    color: #666;
    line-height: 1.7;
  }

  /* 小さい補足リスト（箇条書き） */
  .doc-sublist {
    margin-top: 6px;
    padding-left: 1.2em;
    font-size: 14px;
    color: #666;
    line-height: 1.7;
  }

  .doc-sublist li {
    margin-bottom: 2px;
  }

  /* 書類項目の区切り */
  .doc-item {
    margin-bottom: 14px;
  }

  .doc-item:last-child {
    margin-bottom: 0;
  }

  /* アクセントライン（カテゴリ区切り） */
  .doc-table .category-border-top td {
    border-top: 3px solid #ffe132;
  }

  /* レスポンシブ */
  @media (max-width: 640px) {
    .l-wrapper {
      padding: 0 12px;
      margin: 24px auto;
    }

    .section-title {
      font-size: 20px;
      margin-bottom: 24px;
    }

    .doc-table thead th {
      font-size: 13px;
      padding: 10px 12px;
    }

    .doc-table thead th:first-child {
      width: 80px;
    }

    .doc-table .category-cell {
      font-size: 13px;
      padding: 12px 8px;
    }

    .doc-table .content-cell {
      padding: 12px;
      font-size: 14px;
    }

    .doc-note,
    .doc-exception,
    .doc-sublist {
      font-size: 12px;
    }
  }
</style>
@endsection

