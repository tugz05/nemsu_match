/** User as embedded in post/comment responses */
export interface PostUser {
    id: number;
    display_name: string;
    fullname: string;
    profile_picture: string | null;
    academic_program?: string;
}

/** Post from API (feed) */
export interface Post {
    id: number;
    user_id: number;
    user: PostUser;
    content: string;
    image: string | null;
    images?: string[] | null;
    images_list?: string[];
    likes_count: number;
    comments_count: number;
    reposts_count: number;
    is_liked_by_user: boolean;
    is_followed_by_user?: boolean;
    is_own_post?: boolean;
    time_ago: string;
}

/** Comment (or reply) from API */
export interface PostComment {
    id: number;
    user_id: number;
    user: PostUser;
    content: string;
    time_ago: string;
    likes_count: number;
    is_liked_by_user?: boolean;
    replies?: PostComment[];
}
